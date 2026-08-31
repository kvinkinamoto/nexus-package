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
    <div class="card">
        <div class="card-body">
            <form wire:submit="save">
                @if($moduleConfig->wizard && !empty($moduleConfig->tabs))
                    @include('nexus::' . config('nexus.template') . '.livewire.wizard_progress')

                    @foreach(array_keys($moduleConfig->tabs) as $index => $tabName)
                        <div wire:key="wizard-pane-{{ $tabName }}" @if($index !== $currentStep) style="display:none" @endif>
                            @foreach($this->fieldsForTab($tabName) as $field)
                                @if($this->isFieldVisible($field))
                                    <div wire:key="field-{{ $field->name }}">
                                        @include('nexus::' . config('nexus.template') . '.livewire.field_types.dispatch', ['field' => $field])
                                    </div>
                                @endif
                            @endforeach

                            <div class="d-flex justify-content-between mt-3 wizard-pane-nav">
                                <button type="button" class="btn btn-outline-secondary" wire:click="prevStep" @if($index === 0) style="visibility:hidden" @endif>
                                    <i class="{{ nexus_icon('chevron_left') }} align-middle me-1"></i>@lang('nexus::translate.wizard_back')
                                </button>
                                @if($index !== count($moduleConfig->tabs) - 1)
                                    <button type="button" class="btn btn-primary" wire:click="nextStep">
                                        @lang('nexus::translate.wizard_next')<i class="{{ nexus_icon('chevron_right') }} align-middle ms-1"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach($moduleConfig->form->fields as $field)
                        @if($this->isFieldVisible($field))
                            <div wire:key="field-{{ $field->name }}">
                                @include('nexus::' . config('nexus.template') . '.livewire.field_types.dispatch', ['field' => $field])
                            </div>
                        @endif
                    @endforeach
                @endif

                <div class="mt-3 {{ ($moduleConfig->wizard && !empty($moduleConfig->tabs) && $currentStep !== count($moduleConfig->tabs) - 1) ? 'd-none' : '' }}">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                        @lang('nexus::translate.save')
                    </button>
                    @if($embedded)
                        <button type="button" class="btn btn-light" wire:click="cancel">
                            @lang('nexus::translate.cancel')
                        </button>
                    @else
                        <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'index']) }}" class="btn btn-light">
                            @lang('nexus::translate.cancel')
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
