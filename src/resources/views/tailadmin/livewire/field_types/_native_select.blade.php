{{--
    Shared Alpine dropdown for every plain single-value select-style field
    type (select, enum, gender, wishlistable_type_field, custom_delivery_type,
    custom_delivery_method, custom_payment_method) — replaces Choices.js.

    Choices wrapped the underlying <select> in its own markup and only read
    the `selected` HTML attribute at (re)init time. Livewire's morph strips
    that wrapper back off on every update, so the widget needed
    re-initializing on every render (app.js's 'morphed' hook), AND every
    option needed @selected() re-flagging from live data each render or the
    control would revert to showing the placeholder right after a pick even
    though $wire's value was correct underneath. Two of these seven callers
    ('select', 'enum') had that @selected() fix; the other five didn't and
    were still silently reverting on every pick. A plain Alpine dropdown has
    no separate init step and no copy of the value to fall out of sync —
    $wire is read fresh on every render, same as any other Blade output.

    Expects: $field (FieldConfigDto), $options (array of ['value'=>, 'label'=>]).
    Optional: $placeholder (defaults to nexus::translate.chooseOption),
    $nullable (defaults to true — whether a "clear" entry is offered; a
    field whose value is never null, e.g. a backed enum with a default,
    passes false).
--}}
@php
    $errorKey = "data.{$field->name}";
    $triggerClass = 'flex h-11 w-full items-center justify-between gap-2 rounded-lg border bg-transparent px-4 py-2.5 text-left text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
    $selectedValue = $this->data[$field->name] ?? null;
    $selectedOption = collect($options)->first(fn ($option) => $option['value'] == $selectedValue);
    $isDisabled = $field->isDisabledForAction($action ?? null);
    $placeholderText = $placeholder ?? __('nexus::translate.chooseOption');
    $isNullable = $nullable ?? true;
@endphp
<div class="mb-4" wire:key="native-select-{{ $field->name }}" x-data="{ open: false }">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="relative" @click.outside="open = false">
        <button type="button" id="field-{{ $field->name }}" class="{{ $triggerClass }}"
            @disabled($isDisabled) @click="open = !open">
            <span class="truncate {{ $selectedOption ? '' : 'text-gray-400' }}">{{ $selectedOption['label'] ?? $placeholderText }}</span>
            <i class="bx bx-chevron-down shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
        </button>

        @unless($isDisabled)
            <div x-show="open" x-cloak class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                @if($isNullable)
                    <button type="button" wire:click="$set('data.{{ $field->name }}', '')" @click="open = false"
                        class="block w-full px-3 py-2 text-left text-sm text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5">
                        {{ $placeholderText }}
                    </button>
                @endif
                @foreach($options as $option)
                    <button type="button" wire:click="$set('data.{{ $field->name }}', '{{ addslashes((string) $option['value']) }}')" @click="open = false"
                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5 {{ $selectedValue == $option['value'] ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $option['label'] }}
                    </button>
                @endforeach
            </div>
        @endunless
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
