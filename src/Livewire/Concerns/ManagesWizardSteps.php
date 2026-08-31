<?php

namespace Nodex\Nexus\Livewire\Concerns;

/**
 * ModuleForm's #[Module(wizard: true)] step navigation — per-step validation
 * plus next/prev/goto. Extracted verbatim from ModuleForm (no behavior
 * change); see that class's $currentStep docblock for the "one final save"
 * contract this deliberately doesn't touch.
 */
trait ManagesWizardSteps
{
    /**
     * Validates only the step being left, mirroring wizard_js.blade.php's
     * per-pane :invalid check — not cumulative, since save() runs the full
     * validate() regardless of how steps were visited. rules() keys are
     * already remapped onto "data." / "relationRows." property paths (see
     * remapRuleKey()), so a rule "belongs" to this step when its key is or
     * starts with one of the step's field property paths.
     */
    public function nextStep(): void
    {
        $moduleConfig = $this->resolveModuleConfig();
        $tabNames = array_keys($moduleConfig->tabs);

        if (empty($tabNames)) {
            return;
        }

        $this->normalizeBooleanFields($moduleConfig);

        $tabName = $tabNames[$this->currentStep] ?? null;

        if ($tabName !== null) {
            $stepFieldNames = collect($this->fieldsForTab($tabName))->pluck('name')->all();
            $stepRules = collect($this->rules())
                ->filter(function ($rule, string $key) use ($stepFieldNames) {
                    foreach ($stepFieldNames as $name) {
                        if ($key === "data.{$name}" || str_starts_with($key, "data.{$name}.")
                            || $key === "relationRows.{$name}" || str_starts_with($key, "relationRows.{$name}.")) {
                            return true;
                        }
                    }

                    return false;
                })
                ->all();

            if (! empty($stepRules)) {
                $this->validate($stepRules);
            }
        }

        $this->currentStep = min($this->currentStep + 1, count($tabNames) - 1);
    }

    public function prevStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 0);
    }

    public function goToStep(int $step): void
    {
        $lastStep = count($this->resolveModuleConfig()->tabs) - 1;
        $this->currentStep = max(0, min($step, $lastStep));
    }
}
