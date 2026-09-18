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
    <div class="mb-4">
        @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

        @foreach($productAttributes as $productAttribute)
            @php $attribute = $productAttribute->attribute; @endphp
            @if($attribute)
                @php
                    $errorKey = "data.attribute_options.{$productAttribute->id}";
                    $selectClass = 'w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
                @endphp
                <div class="mb-3">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $attribute->name }}</label>
                    <select multiple wire:model="data.attribute_options.{{ $productAttribute->id }}"
                        class="{{ $selectClass }}">
                        @foreach($attribute->options as $option)
                            <option value="{{ $option->id }}">{{ $option->value }}</option>
                        @endforeach
                    </select>
                    @error($errorKey)
                        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        @endforeach
    </div>
@endif
