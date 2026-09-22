@php
    $errorKey = "relationRows.{$field->name}.{$index}.data.{$blockField->name}";
    $inputClass = 'h-10 w-full rounded-lg border bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
@if($blockField->translatable)
    @foreach($this->activeLocales() as $locale)
        @php $localeErrorKey = "{$errorKey}.{$locale}"; @endphp
        <div class="mb-1.5 flex items-center gap-2">
            <span class="flex h-10 w-12 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $locale }}</span>
            <input type="text" wire:model="relationRows.{{ $field->name }}.{{ $index }}.data.{{ $blockField->name }}.{{ $locale }}"
                class="{{ $inputClass }}">
        </div>
        @error($localeErrorKey)
            <p class="mb-1.5 text-xs text-error-500">{{ $message }}</p>
        @enderror
    @endforeach
@else
    <input type="text" wire:model="relationRows.{{ $field->name }}.{{ $index }}.data.{{ $blockField->name }}"
        class="{{ $inputClass }}">
    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
@endif
