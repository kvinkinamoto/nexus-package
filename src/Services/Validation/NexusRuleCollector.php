<?php

namespace Nodex\Nexus\Services\Validation;

use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\RelationConfigParamsEnum;
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
 * Note this DTO is NOT the same one AdminFormBuilding listeners mutate —
 * that event only fires from FormBuilder, a GET-only concern, never during
 * POST validation.
 *
 * Merge order:
 *   1. Type defaults (FieldTypeRegistry::getDefaultRules())
 *   2. FieldConfigDto::$rules, then $storeRules/$updateRules for the given action
 *   3. isRequired -> ensure 'required' (or 'nullable' when not required)
 *   4. isTranslate -> key becomes "{name}.*"
 *   5. #[RepeaterField(rules:)] -> "relation.{relation}.*.{column}"
 *   6. showWhen -> ['exclude'] when FieldVisibilityEvaluator says hidden
 *      (always wins — reapplied after step 7 so it can't resurrect
 *      validation for a field the form itself won't submit)
 *   7. GatheringValidationRules event, then nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action)
 *      — deliberately both, not one or the other: a module's own Listeners/
 *      folder can react without a plugin class, a reusable cross-module
 *      plugin uses the filter, same call site either way.
 *
 * A module WITH a dedicated Request gets this same filter applied a second,
 * independent way too — see NexusServiceProvider::registerValidationRulesFilter(),
 * which hooks Illuminate\Contracts\Validation\Factory::resolver() and so
 * catches every Validator built anywhere, including one built from a
 * dedicated Request's own hand-written rules() that never calls collect() at
 * all. This collect()-internal call stays regardless, since a caller that
 * only wants the assembled rules array (never builds a real Validator, e.g.
 * the no-dedicated-Request case below, or anyone introspecting rules without
 * validating) would otherwise never see the filter applied. The two overlap
 * — for a plain call to collect() followed immediately by validator($data,
 * $rules)->validate() (the no-dedicated-Request path), the filter runs
 * twice on the same rules. Accepted: a plugin filter here is expected to be
 * an idempotent append/adjust (see ExamplePlugin's own example), so running
 * it twice produces the same effective rule set as running it once.
 *
 * Steps 1-4/6-8 only apply to non-relation fields. A #[Field(type: 'relation')]
 * field posts as relation[{name}] (see field_types/relation.blade.php), not a
 * top-level {name} key, and its shape varies by relation type (single value
 * vs array vs pivot data) — collectFieldRules()'s type/translate/required
 * logic doesn't apply to it. Instead every relation field gets a minimal
 * default of its own — collectRelationDefaultRules(), keyed by cardinality
 * (RelationConfigDto::$type) and RelationConfigDto::$isRequired — so
 * "relation.{name}" always has SOME rule and therefore always survives
 * Laravel's validated() filtering, even when a module defines no dedicated
 * Request at all. A #[RepeaterField]-carrying relation gets both this
 * top-level default (so the key resolves to an array even with zero rows)
 * AND its existing per-column "relation.{name}.*.{column}" rules below —
 * the two don't conflict, Laravel validates parent-array and
 * wildcard-children rules independently.
 *
 * This default is deliberately minimal (nullable/required, plus 'array' for
 * a multi relation) — it has no way to know a related table's key column or
 * whether a submitted id should exist there, so it only guarantees the
 * field survives into validated() and (when marked required) can't be
 * submitted empty. A module with a stricter need (Rule::exists(), a custom
 * per-item shape, ...) still supplies its own dedicated Request — collect()
 * only ever runs when that Request is absent or empty (see
 * assertRelationCoverage()'s docblock), so the dedicated Request's own
 * rules simply replace this default outright rather than merging with it.
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
    public function collect(DefaultModuleConfigurationDto $moduleConfig, string $action, array $inputValues = []): array
    {
        $rules = [];
        $excludedKeys = [];

        foreach ($moduleConfig->form->fields as $name => $field) {
            $relationConfig = $moduleConfig->relations->is_available[$name] ?? null;

            if ($relationConfig && !$this->postsRelationData($field)) {
                // A read-only relation display (relationManager and friends —
                // see self::postsRelationData()'s docblock) never submits a
                // "relation.{name}" key at all, so there's nothing to validate
                // and nothing whose absence would need covering.
            } elseif (!$relationConfig) {
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
            } else {
                $key = "relation.{$name}";

                if (!empty($field->showWhen) && !$this->visibilityEvaluator->isVisible($field, $inputValues)) {
                    $rules[$key] = ['exclude'];
                    $excludedKeys[] = $key;
                } else {
                    $rules[$key] = $this->collectRelationDefaultRules($relationConfig);
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

        event(new GatheringValidationRules($moduleConfig, $rules, $action));
        $rules = nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action);

        foreach ($excludedKeys as $key) {
            $rules[$key] = ['exclude'];
        }

        return $rules;
    }

    /**
     * A relation-backed field whose type has no editable control at all —
     * relationManager (field_types/relationManager.blade.php) is the
     * confirmed built-in example, a read-only list with links out to the
     * related module, never a form input — has nothing to validate and
     * nothing whose absence needs flagging: it never appears in submitted
     * data in the first place, so requiring rules for it would be checking
     * for a key that structurally cannot exist. 'relation' (the
     * belongsTo/belongsToMany picker, field_types/relation.blade.php) and
     * any #[RepeaterField]-carrying field are the only relation field types
     * that actually post a value.
     */
    private function postsRelationData(object $field): bool
    {
        return $field->type === 'relation' || !empty($field->repeaterColumns);
    }

    /**
     * Minimal baseline for a relation field the collector otherwise knows
     * nothing about beyond its cardinality and RelationConfigDto::$isRequired
     * — see this class's own docblock for why it stops there.
     */
    private function collectRelationDefaultRules(object $relationConfig): array
    {
        $isMultiple = in_array($relationConfig->type, [
            RelationConfigParamsEnum::BELONGS_TO_MANY->value,
            RelationConfigParamsEnum::HAS_MANY->value,
        ], true);

        $rules = [$relationConfig->isRequired ? 'required' : 'nullable'];

        if ($isMultiple) {
            $rules[] = 'array';
        }

        return $rules;
    }

    /**
     * Dev-time safety net for the exact fragility that made this class's
     * relation defaults necessary in the first place: StoreActionMethod,
     * UpdateActionMethod and Livewire\ModuleForm::rules() all use a
     * dedicated Request's rules() EXCLUSIVELY the moment it's non-empty
     * (see each one's own docblock) — collect()'s relation defaults never
     * run on that branch, so a hand-written Request that forgets a relation
     * field silently loses it the same way an absent Request used to,
     * before collectRelationDefaultRules() existed. Deliberately not fixed
     * by merging collect()'s output into that branch instead: a dedicated
     * Request's rules() is what Laravel's own FormRequest lifecycle
     * resolves and validates against BEFORE this code ever sees it (so any
     * withValidator() cross-field check it defines already ran) — replacing
     * what it validated with a merged set after the fact would validate
     * different rules than what actually ran. A module wanting the merge
     * uses Concerns/ComposesNexusRules instead, which merges INSIDE rules()
     * itself, before that lifecycle resolves anything.
     *
     * So this only fails loud, in non-production, instead of failing
     * silent in every environment — call it wherever $moduleRequest->rules()
     * is about to be trusted exclusively.
     */
    public function assertRelationCoverage(DefaultModuleConfigurationDto $moduleConfig, array $requestRules): void
    {
        if (app()->isProduction()) {
            return;
        }

        $missing = [];

        foreach ($moduleConfig->form->fields as $name => $field) {
            if (!isset($moduleConfig->relations->is_available[$name]) || !$this->postsRelationData($field)) {
                continue;
            }

            $prefix = "relation.{$name}";
            $covered = array_key_exists($prefix, $requestRules)
                || array_key_exists("{$prefix}.*", $requestRules)
                || collect($requestRules)->keys()->contains(fn ($key) => str_starts_with($key, "{$prefix}."));

            if (!$covered) {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(sprintf(
                '[%s] dedicated Request validates some fields but none of these relation field(s): %s. '
                .'Laravel\'s validated() silently drops any key rules() doesn\'t cover, so the submitted '
                .'value(s) would never reach StoreRelationActionMethod. Add explicit "relation.%s" (or '
                .'"relation.%s.*") rule(s), or use Nodex\Nexus\Concerns\ComposesNexusRules on this Request '
                .'to inherit NexusRuleCollector\'s baseline automatically.',
                $moduleConfig->name,
                implode(', ', $missing),
                $missing[0],
                $missing[0],
            ));
        }
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
