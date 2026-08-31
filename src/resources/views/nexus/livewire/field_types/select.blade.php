{{--
    Livewire Етап 6 — port of templates/field_types/select.blade.php. Options
    come from $field->customData ([['value'=>..., 'name'=>...], ...]),
    populated by an AdminFormBuilding listener (see Widget's
    PopulateWidgetSelectOptions) — ModuleForm::resolveModuleConfig() now
    dispatches that event and caches the resulting DTO for the rest of this
    round-trip, so customData set there is still present by the time this
    partial renders (see that method's docblock for why that wasn't true
    before this session).
--}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    {{--
        .live, not plain wire:model: a select's own value only ever changes
        via a deliberate pick (not a per-keystroke stream like a text input),
        and this is currently the only field type a dependent field (Widget's
        widget_view/config, via updatedDataWidgetKey()) needs to react to
        immediately rather than waiting for the next unrelated round-trip.
    --}}
    <select id="field-{{ $field->name }}" wire:model.live="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        <option value="">@lang('nexus::translate.chooseOption')</option>
        @foreach($field->customData ?? [] as $option)
            <option value="{{ $option['value'] }}">{{ $option['name'] }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
