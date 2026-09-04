{{--
    Reactive replacement for pages/create.blade.php / pages/edit.blade.php's
    full-page POST + redirect cycle, rendered by Nodex\Nexus\Livewire\ModuleForm
    for any #[Module(livewire: true)] module. Pilot-stage scope: plain fields,
    #[RepeaterField], ajax relation fields, and #[Module(wizard: true)]
    multi-step forms — see the component's docblock. When $embedded (mounted
    inline inside ModuleTable's slide-over panel instead of a whole page),
    the cancel/save actions close the panel instead of navigating.
--}}
<div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.02]">
        @error('form')
            {{--
                Same 'error-*' red the per-field @error() partials already use
                (see .ai/rules for why it must stay full literal class names).
                A save() failure that isn't a validation error (e.g. a DB or
                hook exception) has nowhere else to surface in embedded/
                slide-over mode — no page navigation to carry a session-flash
                toast through — so it's reported here via $this->addError('form', ...)
                instead, using the exact same visual language as everything else.
            --}}
            <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-800 dark:bg-error-500/10 dark:text-error-400">
                {{ $message }}
            </div>
        @enderror
        <form wire:submit="save">
            @if($moduleConfig->wizard && !empty($moduleConfig->tabs))
                @include('nexus::' . config('nexus.template') . '.livewire.wizard_progress')

                @foreach(array_keys($moduleConfig->tabs) as $index => $tabName)
                    <div wire:key="wizard-pane-{{ $tabName }}" @if($index !== $currentStep) class="hidden" @endif>
                        @include('nexus::' . config('nexus.template') . '.livewire.section_cards', ['fields' => $this->fieldsForTab($tabName)])

                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/5">
                            <button type="button" wire:click="prevStep" @if($index === 0) class="invisible" @endif
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                <i class="{{ nexus_icon('chevron_left') }}"></i>@lang('nexus::translate.wizard_back')
                            </button>
                            @if($index !== count($moduleConfig->tabs) - 1)
                                <button type="button" wire:click="nextStep"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                                    @lang('nexus::translate.wizard_next')<i class="{{ nexus_icon('chevron_right') }}"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                @include('nexus::' . config('nexus.template') . '.livewire.section_cards')
            @endif

            <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-4 dark:border-white/5 {{ ($moduleConfig->wizard && !empty($moduleConfig->tabs) && $currentStep !== count($moduleConfig->tabs) - 1) ? 'hidden' : '' }}">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:opacity-60">
                    <span wire:loading wire:target="save" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    @lang('nexus::translate.save')
                </button>
                @if($embedded)
                    <button type="button" wire:click="cancel"
                        class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                        @lang('nexus::translate.cancel')
                    </button>
                @else
                    <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'index']) }}"
                        class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                        @lang('nexus::translate.cancel')
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
