{{--
    Generic (non-image/video) document field — same elFinder popup mechanism
    as image.blade.php (see its own docblock for how processSelectedFile()
    writes the chosen path into the hidden-by-class input below), just
    showing a filename/link instead of an image preview since elFinder
    browses/picks any file type here, not only images.
--}}
@php
    $errorKey = "data.{$field->name}";
    $value = $this->data[$field->name] ?? null;
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <input type="text" class="hidden" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}">

    <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400">
            <i class="{{ nexus_icon('file') }}"></i>
        </div>
        <div class="flex min-w-0 flex-1 flex-col gap-2">
            @if($value)
                <a href="/{{ ltrim($value, '/') }}" target="_blank" rel="noopener" class="truncate text-sm text-brand-600 hover:underline dark:text-brand-400">
                    {{ basename($value) }}
                </a>
            @endif
            <div class="flex gap-2">
                <button type="button" class="popup_selector rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" data-inputid="field-{{ $field->name }}">
                    @lang('nexus::translate.chooseFile')
                </button>
                @if($value)
                    <button type="button" wire:click="clearImage('{{ $field->name }}')"
                        class="rounded-lg border border-error-200 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
                        @lang('nexus::translate.remove')
                    </button>
                @endif
            </div>
        </div>
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
