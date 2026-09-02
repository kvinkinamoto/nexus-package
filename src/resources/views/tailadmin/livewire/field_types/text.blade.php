{{-- Livewire Етап 6 — textarea counterpart of string.blade.php; same translatable-locale-rows approach. --}}
@php
    $errorKey = "data.{$field->name}";
    $areaClass = 'w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    @if($field->isTranslate)
        @foreach($this->activeLocales() as $locale)
            @php $localeErrorKey = "{$errorKey}.{$locale}"; @endphp
            <div class="mb-1.5 flex items-start gap-2">
                <span class="flex h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $locale }}</span>
                <textarea rows="4" wire:model="data.{{ $field->name }}.{{ $locale }}"
                    @disabled($field->isDisabledForAction($action ?? null))
                    class="{{ $areaClass }}"></textarea>
            </div>
            @error($localeErrorKey)
                <p class="mb-1.5 text-xs text-error-500">{{ $message }}</p>
            @enderror
        @endforeach
    @else
        <textarea rows="4" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="{{ $areaClass }}"></textarea>

        @error($errorKey)
            <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
        @enderror
    @endif
</div>
