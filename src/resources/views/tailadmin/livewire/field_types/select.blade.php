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
    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
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
        data-choices data-choices-sorting-false
        class="{{ $selectClass }}">
        <option value="">@lang('nexus::translate.chooseOption')</option>
        @foreach($field->customData ?? [] as $option)
            {{--
                Choices.js reads the `selected` HTML attribute (not the live
                DOM .value property) when (re)initializing on the clone
                app.js's 'morphed' hook makes every round-trip — without it,
                the widget shows the placeholder after every pick even
                though the underlying <select>/Livewire data is correct.
            --}}
            <option value="{{ $option['value'] }}" @selected(($this->data[$field->name] ?? null) == $option['value'])>{{ $option['name'] }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
