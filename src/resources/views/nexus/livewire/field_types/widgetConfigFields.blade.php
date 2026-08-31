{{--
    Livewire Етап 6 — port of Widget's own field_types/widgetConfigFields.blade.php.
    The legacy version fetched a fresh copy of the selected widget's own
    configFields() + re-rendered this whole block via AJAX whenever #widget_key
    changed (AdminController::onWidgetKeyChanged); here that's just "read
    data.widget_key live, same as any other reactive Livewire read" — no
    separate refresh step, no JS. ModuleForm::updatedDataWidgetKey() clears
    data.config to [] whenever the key changes so a stale value from the
    previous widget's fields can't leak into the newly selected widget's.

    Each configFields() entry (itself a plain FieldConfigDto — see
    HelloWorldWidget::configFields() for the canonical shape) is cloned with
    its name rewritten onto "config.{name}" and handed straight to the normal
    dispatch chain: wire:model="data.config.{name}" needs no new field-type
    partials of its own as long as the widget only uses already-supported
    types (string/text/number/boolean/enum/...) — a widget author reaching
    for something this pilot doesn't support yet still falls through to
    unsupported.blade.php per field, same as anywhere else.
--}}
@php
    $__widgetKey = $this->data['widget_key'] ?? null;
    $__configFields = [];

    if ($__widgetKey) {
        $__entry = app(\Nodex\Nexus\Services\Widgets\WidgetRegistry::class)->find($__widgetKey);
        if ($__entry) {
            $__configFields = $__entry['class']::configFields();
        }
    }
@endphp
<div class="mb-3">
    @if(!empty($field->label))
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])
    @endif

    <div class="border rounded p-3">
        @forelse($__configFields as $__configField)
            @php
                $__clonedField = new \Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto(
                    name: "config.{$__configField->name}",
                    type: $__configField->type,
                    label: $__configField->label,
                    default: $__configField->default,
                    isRequired: $__configField->isRequired,
                );
            @endphp
            @include('nexus::' . config('nexus.template') . '.livewire.field_types.dispatch', ['field' => $__clonedField])
        @empty
            <p class="text-muted small mb-0">@lang('widget::translate.select_a_widget_first')</p>
        @endforelse
    </div>
</div>
