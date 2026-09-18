{{--
    Livewire wizard step indicator — same look as
    templates/forminputs/wizard_nav.blade.php, but jumps steps via
    wire:click="goToStep(...)" instead of a client-side JS listener (see
    ModuleForm::goToStep()). Expects $moduleConfig and $currentStep in scope.
--}}
<div class="mb-6 flex flex-wrap items-center">
    @foreach(array_values($moduleConfig->tabs) as $index => $tab)
        <div wire:click="goToStep({{ $index }})" role="button"
            class="flex cursor-pointer items-center gap-2 {{ $index === $currentStep || $index < $currentStep ? 'opacity-100' : 'opacity-60' }}">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                {{ $index === $currentStep ? 'bg-brand-500 text-white' : ($index < $currentStep ? 'bg-success-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400') }}">
                {{ $index + 1 }}
            </span>
            <span class="hidden text-sm font-medium text-gray-700 sm:inline dark:text-gray-300">
                @lang(Str::lcfirst($module->name) . '::' . 'translate.' . $tab->label)
            </span>
        </div>
        @if(!$loop->last)
            <div class="mx-2 h-0.5 flex-1 bg-gray-200 dark:bg-gray-800"></div>
        @endif
    @endforeach
</div>
