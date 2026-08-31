{{--
    Livewire wizard step indicator — same look as
    templates/forminputs/wizard_nav.blade.php, but jumps steps via
    wire:click="goToStep(...)" instead of a client-side JS listener (see
    ModuleForm::goToStep()). Expects $moduleConfig and $currentStep in scope.
--}}
<div class="wizard-progress d-flex flex-wrap align-items-center mb-4">
    @foreach(array_values($moduleConfig->tabs) as $index => $tab)
        <div class="wizard-step {{ $index === $currentStep ? 'is-active' : '' }} {{ $index < $currentStep ? 'is-done' : '' }}"
             wire:click="goToStep({{ $index }})" role="button">
            <span class="wizard-step-badge">{{ $index + 1 }}</span>
            <span class="wizard-step-label">
                @lang($module->name . '::' . 'translate.' . $tab->label)
            </span>
        </div>
        @if(!$loop->last)
            <div class="wizard-step-connector"></div>
        @endif
    @endforeach
</div>
<style>
    .wizard-step { display: flex; align-items: center; gap: .5rem; cursor: pointer; opacity: .6; }
    .wizard-step.is-active, .wizard-step.is-done { opacity: 1; }
    .wizard-step-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; flex: 0 0 auto; border-radius: 50%;
        background: var(--bs-secondary-bg, #eee); color: var(--bs-secondary-color, #666);
        font-weight: 600; font-size: .875rem;
    }
    .wizard-step.is-active .wizard-step-badge { background: var(--bs-primary); color: #fff; }
    .wizard-step.is-done .wizard-step-badge { background: var(--bs-success); color: #fff; }
    .wizard-step-connector { flex: 1 1 2rem; height: 2px; background: var(--bs-border-color, #dee2e6); align-self: center; margin: 0 .5rem; }
</style>
