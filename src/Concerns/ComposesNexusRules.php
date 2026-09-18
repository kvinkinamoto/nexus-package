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
 * This is the recommended way to write a dedicated Request for a module
 * with relation fields, not just a convenience: StoreActionMethod,
 * UpdateActionMethod and Livewire\ModuleForm::rules() all use a dedicated
 * Request's rules() EXCLUSIVELY the moment it's non-empty — NexusRuleCollector
 * (and its relation-field defaults) never runs at all on that branch, so a
 * plain `extends FormRequest` with a hand-written rules() array must
 * re-declare every field itself, relation fields included, or the omitted
 * key is silently dropped by Laravel's validated() before it ever reaches
 * StoreRelationActionMethod. Using this trait avoids that by construction —
 * the collected baseline is what rules() itself returns, merged with
 * extraRules(), so nothing is missing by omission. (A dev-time guard,
 * NexusRuleCollector::assertRelationCoverage(), still catches a Request
 * that skips this trait AND forgets a relation field — but this trait is
 * how you avoid needing that guard to catch anything.)
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

        $collected = app(NexusRuleCollector::class)->collect($moduleConfig, $action, $this->all());

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
