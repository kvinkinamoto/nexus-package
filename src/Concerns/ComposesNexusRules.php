<?php

namespace Nodex\Nexus\Concerns;

use Nodex\Nexus\Services\Validation\NexusRuleCollector;

/**
 * `use` this on a module's AdminStoreRequest/AdminUpdateRequest (no base
 * class change needed — they keep extending Illuminate's FormRequest
 * directly) to have field-config-derived rules assembled automatically by
 * NexusRuleCollector, instead of hand-listing every field's rules.
 *
 * Override extraRules() (not rules()) for anything NexusRuleCollector can't
 * express from FieldConfigDto alone — cross-field checks, uniqueness,
 * Rule::in()/exists() against other tables, etc. extraRules() wins over the
 * collected rules for any key it also sets.
 *
 * Note: StoreActionMethod/UpdateActionMethod already run NexusRuleCollector
 * as a baseline for every module and merge any dedicated Request's rules()
 * on top of it (see NexusRuleCollector::resolveRules()) — this trait doesn't
 * change what happens for other modules, only what THIS Request's own
 * rules() call returns, for a Request class used somewhere outside that
 * orchestration (a custom controller, a Livewire form, etc.) or for
 * explicitness about intent.
 */
trait ComposesNexusRules
{
    public function rules(): array
    {
        $module = $this->route('module');
        $moduleConfig = $module->config ?? null;

        if (!$moduleConfig) {
            return $this->extraRules();
        }

        $action = str_contains(static::class, 'Update') ? 'update' : 'store';

        $collected = app(NexusRuleCollector::class)->collect($moduleConfig, $action, $this->all(), $this);

        return array_replace($collected, $this->extraRules());
    }

    /**
     * Rules NexusRuleCollector can't derive from FieldConfigDto — merged on
     * top of the collected rules, winning per-key.
     */
    protected function extraRules(): array
    {
        return [];
    }
}
