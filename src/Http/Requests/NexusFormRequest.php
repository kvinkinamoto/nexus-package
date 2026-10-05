<?php

namespace Nodex\Nexus\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Attributes\Field as FieldAttr;
use Nodex\Nexus\Events\PreparingForValidation;

abstract class NexusFormRequest extends FormRequest
{
    /**
     * Every request in this codebase authorizes unconditionally — real access
     * control happens earlier, via ModuleManager::checkPermission() in the
     * controller/Livewire action before a Request class is ever resolved.
     * Override this in a subclass for the rare request that needs its own
     * check.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the core validation rules for the module.
     *
     * Override this in your FormRequest to define custom rules.
     * If it returns an empty array, rules are auto-collected from #[Field] attributes on the model.
     */
    protected function moduleRules(): array
    {
        return [];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Override this in your FormRequest to define custom rules — including
     * withValidator() too, if you need it; neither is touched by Nexus.
     * The nexus.validation.rules plugin filter still applies regardless of
     * how this is overridden — see NexusServiceProvider::registerValidationRulesFilter(),
     * which hooks Illuminate\Contracts\Validation\Factory::resolver() (the
     * same "guaranteed regardless of subclass" mechanism Laravel's own
     * FormRequestServiceProvider uses for validateResolved()) rather than
     * this class, so nothing here needs to be final or otherwise protected
     * from being overridden.
     */
    public function rules(): array
    {
        $rules = $this->moduleRules();

        // If no explicit rules defined, auto-collect from #[Field] attributes on the model
        if (empty($rules)) {
            $rules = $this->collectRulesFromAttributes();
        }

        return $rules;
    }

    /**
     * validated() only returns keys covered by a rule, and modules list
     * "relation.{name}.*.{column}" rules per repeater column but never the
     * row "id" — so every existing row looked new and StoreRelationActionMethod
     * deleted and recreated all of them on each save (changing their ids and
     * cascading into anything that referenced them). Keep the submitted id of
     * rows that passed validation; StoreRelationActionMethod only matches it
     * against rows of the relation being saved.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null || ! is_array($validated['relation'] ?? null)) {
            return $validated;
        }

        $submitted = $this->input('relation');
        if (! is_array($submitted)) {
            return $validated;
        }

        foreach ($validated['relation'] as $name => $rows) {
            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $index => $row) {
                $id = $submitted[$name][$index]['id'] ?? null;

                if (is_array($row) && ! isset($row['id']) && is_scalar($id) && $id !== '') {
                    $validated['relation'][$name][$index]['id'] = $id;
                }
            }
        }

        return $validated;
    }

    /**
     * Auto-collect validation rules from #[Field(rules: ...)] attributes on the module's model.
     * Translatable fields get their key suffixed with .* (e.g. title.*).
     */
    protected function collectRulesFromAttributes(): array
    {
        $module = request()->route('module');
        if (! $module) {
            return [];
        }

        $modelClass = $module->config->model ?? null;
        if (! $modelClass || ! class_exists($modelClass)) {
            return [];
        }

        $action = request()->route('action');
        $isUpdate = in_array($action, ['update', 'edit']);

        $rules = [];
        $reflection = new \ReflectionClass($modelClass);

        foreach ($reflection->getProperties() as $property) {
            $fieldAttrs = $property->getAttributes(FieldAttr::class);
            if (empty($fieldAttrs)) {
                continue;
            }

            /** @var FieldAttr $fieldMeta */
            $fieldMeta = $fieldAttrs[0]->newInstance();
            $name = $property->getName();

            // Pick the right rule set for this action
            $fieldRules = $isUpdate && ! empty($fieldMeta->updateRules)
                ? $fieldMeta->updateRules
                : (! empty($fieldMeta->storeRules) && ! $isUpdate ? $fieldMeta->storeRules : $fieldMeta->rules);

            if (empty($fieldRules)) {
                continue;
            }

            // Normalize to array
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : (array) $fieldRules;

            // Translatable fields come as arrays from the form (e.g. title[en], title[uk])
            $key = $fieldMeta->translated ? "{$name}.*" : $name;
            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Override this method to modify input data before validation.
     * This is the Nexus-friendly alias of prepareForValidation().
     */
    protected function beforeValidation(): void
    {
        // To be overridden by children
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->beforeValidation();

        // Allow plugins to modify request data before validation runs
        event(new PreparingForValidation($this));
        nexus_action('nexus.validation.preparing', $this);
    }
}
