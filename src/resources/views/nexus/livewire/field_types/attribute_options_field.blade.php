{{--
    Livewire Етап 6 — port of ShopProduct's own field_types/attribute_options_field.blade.php.
    Model-driven like the User *_table partials, not $field->name-driven —
    see ModuleForm::mount()'s 'attribute_options_field' branch for why (posts
    under a hardcoded 'attribute_options' key). Only shows anything once the
    product's `attributes` have actually been saved at least once (same
    limitation the legacy version has — ProductAttribute rows don't exist
    until that first save).
--}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
    $productAttributes = $model ? $model->productAttributes()->with(['attribute.options'])->get() : collect();
@endphp
@if($productAttributes->isNotEmpty())
    <div class="mb-3">
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

        @foreach($productAttributes as $productAttribute)
            @php $attribute = $productAttribute->attribute; @endphp
            @if($attribute)
                <div class="mb-2">
                    <label class="form-label">{{ $attribute->name }}</label>
                    <select multiple wire:model="data.attribute_options.{{ $productAttribute->id }}"
                        class="form-control">
                        @foreach($attribute->options as $option)
                            <option value="{{ $option->id }}">{{ $option->value }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        @endforeach
    </div>
@endif
