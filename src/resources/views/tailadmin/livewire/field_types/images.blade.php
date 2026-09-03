{{--
    Multi-image gallery, previously documented on #[Field] but unreachable in
    the Livewire dispatch chain (fell to unsupported.blade.php). Unlike the
    legacy templates/field_types/images.blade.php (Vue 3, each image its own
    #[Relation]-backed row), this is a plain JSON-array-of-paths column — no
    relation needed, consistent with how the Livewire pilot keeps everything
    else in $this->data. Each pick reuses the same elFinder popup
    image.blade.php already wires up; app.js's delegated `input` listener on
    [data-multi-field] calls addMultiFileValue()/removeMultiFileValue()
    (ManagesMultiFileFields) directly — there's no single stable element an
    array of unknown length could bind a plain wire:model to.
--}}
@php
    $errorKey = "data.{$field->name}";
    $values = (array) ($this->data[$field->name] ?? []);
    $pickerId = 'field-' . $field->name . '-picker';
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <input type="text" class="hidden" id="{{ $pickerId }}" data-multi-field="{{ $field->name }}">

    @if(! empty($values))
        <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            @foreach($values as $i => $path)
                <div class="relative" wire:key="{{ $field->name }}-{{ $i }}-{{ $path }}">
                    <div class="flex h-24 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
                        <img src="/{{ ltrim($path, '/') }}" class="h-24 w-full object-cover">
                    </div>
                    @unless($field->isDisabledForAction($action ?? null))
                        <button type="button" wire:click="removeMultiFileValue('{{ $field->name }}', {{ $i }})"
                            aria-label="@lang('nexus::translate.remove')"
                            class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-error-500 text-xs text-white shadow-theme-sm hover:bg-error-600">
                            <i class="bx bx-x"></i>
                        </button>
                    @endunless
                </div>
            @endforeach
        </div>
    @endif

    @unless($field->isDisabledForAction($action ?? null))
        <button type="button" class="popup_selector rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5"
            data-inputid="{{ $pickerId }}">
            @lang('nexus::translate.chooseImage')
        </button>
    @endunless

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
