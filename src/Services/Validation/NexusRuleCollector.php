<?php

namespace Nodex\Nexus\Services\Validation;

use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Events\GatheringValidationRules;
use Nodex\Nexus\Services\FieldTypeRegistry;
use Nodex\Nexus\Services\FieldVisibilityEvaluator;

/**
 * Assembles a module's validation rules from FieldConfigDto (already built
 * by AttributeSchemaReader + PluginManager::apply() on the given
 * DefaultModuleConfigurationDto) rather than re-reflecting the model class
 * independently, the way the old (dead) NexusFormRequest::collectRulesFromAttributes()
 * did — that read #[Field] straight off the model's PHP attributes, which a
 * plugin's DTO mutation can't affect.
 *
 * Note this DTO is NOT the same one AdminFormBuilding listeners (like SEO's
 * InjectSeoFields, which adds meta_title/meta_description as form fields for
 * display) mutate — that event only fires from FormBuilder, a GET-only
 * concern, never during POST validation. SEO validation instead comes back
 * through step 7 below: GatheringValidationRules is dispatched independently
 * by THIS class, and InjectSeoValidationRules reacts to it directly via its
 * own SeoHelper lookup, not by reading $moduleConfig->form->fields.
 *

 * Merge order:
 *   1. Type defaults (FieldTypeRegistry::getDefaultRules())
 *   2. FieldConfigDto::$rules, then $storeRules/$updateRules for the given action
 *   3. isRequired -> ensure 'required' (or 'nullable' when not required)
 *   4. isTranslate -> key becomes "{name}.*"
 *   5. #[RepeaterField(rules:)] -> "relation.{relation}.*.{column}"
 *   6. showWhen -> ['exclude'] when FieldVisibilityEvaluator says hidden
 *      (always wins — reapplied after steps 7/8 so neither can resurrect
 *      validation for a field the form itself won't submit)
 *   7. GatheringValidationRules event (mutable $rules, by reference)
 *   8. nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action)
 *
 * Scoped deliberately: steps 1-4/6-8 only apply to non-relation fields.
 * A #[Field(type: 'relation')] field posts as relation[{name}] (see
 * field_types/relation.blade.php), not a top-level {name} key, and its
 * shape varies by relation type (single value vs array vs pivot data) —
 * getting that wrong risks silently breaking relation saves. Relation
 * fields (other than #[RepeaterField] columns, which already post under
 * the same relation[{name}][{index}][{column}] shape StoreRelationActionMethod
 * expects) are left to a dedicated Request's own hand-written rules, exactly
 * as before this collector existed.
 */
class NexusRuleCollector
{
    public function __construct(
        private FieldTypeRegistry $fieldTypeRegistry,
        private FieldVisibilityEvaluator $visibilityEvaluator,
    ) {}

    /**
     * @param  string  $action  'store'|'update' (AvailableActionEnum values)
     * @param  array  $inputValues  Currently-submitted values, used to evaluate showWhen.
     */
    public function collect(DefaultModuleConfigurationDto $moduleConfig, string $action, array $inputValues = [], ?FormRequest $request = null): array
    {
        $rules = [];
        $excludedKeys = [];

        foreach ($moduleConfig->form->fields as $name => $field) {
            $isRelationField = isset($moduleConfig->relations->is_available[$name]);

            if (!$isRelationField) {
                $key = $field->isTranslate ? "{$name}.*" : $name;

                if (!empty($field->showWhen) && !$this->visibilityEvaluator->isVisible($field, $inputValues)) {
                    $rules[$key] = ['exclude'];
                    $excludedKeys[] = $key;
                } else {
                    $fieldRules = $this->collectFieldRules($field, $action);
                    if (!empty($fieldRules)) {
                        $rules[$key] = $fieldRules;
                    }
                }
            }

            foreach ($field->repeaterColumns ?? [] as $column) {
                $columnRules = is_string($column->rules) ? explode('|', $column->rules) : (array) $column->rules;
                if (empty($columnRules) && $column->required) {
                    $columnRules = ['required'];
                }
                if (!empty($columnRules)) {
                    $rules["relation.{$name}.*.{$column->name}"] = $columnRules;
                }
            }
        }

        if ($request) {
            event(new GatheringValidationRules($request, $rules));
        }

        $rules = nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action);

        foreach ($excludedKeys as $key) {
            $rules[$key] = ['exclude'];
        }

        return $rules;
    }

    /**
     * Merges $collect()'s output with a dedicated Request class's own
     * hand-written rules() (steps 2-8 of any hand-written array win per-key
     * over the collector — preserves every currently-hand-written Request's
     * exact validation), except an exclude for a showWhen-hidden field
     * always wins regardless of what the hand-written array says.
     */
    public function resolveRules(DefaultModuleConfigurationDto $moduleConfig, string $action, ?FormRequest $dedicatedRequest, array $inputValues = []): array
    {
        $collected = $this->collect($moduleConfig, $action, $inputValues, $dedicatedRequest);

        $dedicatedRules = [];
        if ($dedicatedRequest && get_class($dedicatedRequest) !== FormRequest::class && method_exists($dedicatedRequest, 'rules')) {
            $dedicatedRules = $dedicatedRequest->rules();
        }

        $merged = array_replace($collected, $dedicatedRules);

        foreach ($collected as $key => $rule) {
            if ($rule === ['exclude']) {
                $merged[$key] = ['exclude'];
            }
        }

        return $merged;
    }

    private function collectFieldRules(object $field, string $action): array
    {
        $rules = $this->fieldTypeRegistry->getDefaultRules($field->type);

        $actionRules = $action === 'update' && !empty($field->updateRules)
            ? $field->updateRules
            : ((!empty($field->storeRules) && $action !== 'update') ? $field->storeRules : $field->rules);

        if (!empty($actionRules)) {
            $rules = array_values(array_unique(array_merge($rules, (array) $actionRules)));
        }

        if (!in_array('required', $rules, true) && !in_array('nullable', $rules, true)) {
            array_unshift($rules, $field->isRequired ? 'required' : 'nullable');
        }

        return $rules;
    }
}
