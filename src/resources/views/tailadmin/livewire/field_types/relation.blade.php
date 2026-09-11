{{--
    Ajax relation field — belongsTo (single, e.g. Cart's `user`) or
    belongsToMany/hasMany (multi, e.g. ShopFilter's `categories`). Search
    runs server-side via RelationService (see
    ModuleForm::updatedRelationSearchQuery()), no Choices.js and no separate
    fetch/HTTP round trip. Non-ajax and hasOne/custom single-relation fields
    are out of scope here and still fall through to
    field_types/unsupported.blade.php.
--}}
@php
    $relationConfig = $module->config->relations->is_available[$field->name] ?? null;
    $isMultiple = $relationConfig && in_array($relationConfig->type, [
        \Nodex\Nexus\Enums\RelationConfigParamsEnum::BELONGS_TO_MANY->value,
        \Nodex\Nexus\Enums\RelationConfigParamsEnum::HAS_MANY->value,
    ], true);
    $isDisabled = $field->isDisabledForAction($action ?? null);
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4" wire:key="relation-{{ $field->name }}" x-data="{ open: false }">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        {{ nexus_trans_label($module->name, $field->label ?? null, $field->name) }}
    </label>

    @if($isMultiple)
        @php
            $selectedIds = (array) ($this->data[$field->name] ?? []);
            $labels = (array) ($this->relationLabels[$field->name] ?? []);
        @endphp
        @if(!empty($selectedIds))
            <div class="mb-2 flex flex-wrap gap-2">
                @foreach($selectedIds as $id)
                    <span class="flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-800 dark:bg-white/5 dark:text-gray-300">
                        {{ $labels[$id] ?? $id }}
                        @unless($isDisabled)
                            <button type="button" wire:click="removeRelationItem('{{ $field->name }}', '{{ $id }}')"
                                aria-label="{{ __('nexus::translate.cancel') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <i class="bx bx-x text-xs"></i>
                            </button>
                        @endunless
                    </span>
                @endforeach
            </div>
        @endif

        @unless($isDisabled)
            <div class="relative" @click.outside="open = false">
                <input type="text" class="{{ $inputClass }}"
                    wire:model.live.debounce.300ms="relationSearchQuery.{{ $field->name }}"
                    @focus="open = true" @click="open = true"
                    placeholder="@lang('nexus::translate.chooseRelation')" autocomplete="off">

                @if(!empty($this->relationSearchResults[$field->name]))
                    <div x-show="open" x-cloak class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                        @foreach($this->relationSearchResults[$field->name] as $option)
                            <button type="button" wire:click="selectRelation('{{ $field->name }}', '{{ $option['id'] }}', '{{ addslashes($option['label']) }}')"
                                @click="open = false"
                                class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                {{ $option['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endunless
    @else
        @php
            $selectedId = $this->data[$field->name] ?? null;
            $selectedLabel = $this->relationLabels[$field->name] ?? null;
        @endphp
        @if($isDisabled)
            <div class="{{ $inputClass }} flex items-center bg-gray-50 dark:bg-white/5">{{ $selectedLabel ?? $selectedId ?? '—' }}</div>
        @else
            <div class="relative">
                @if($selectedId)
                    <div class="{{ $inputClass }} flex items-center justify-between gap-2">
                        <span class="flex-1 truncate">{{ $selectedLabel ?? $selectedId }}</span>
                        <button type="button" wire:click="clearRelation('{{ $field->name }}')" aria-label="{{ __('nexus::translate.cancel') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="bx bx-x text-sm"></i>
                        </button>
                    </div>
                @else
                    <div class="relative" @click.outside="open = false">
                        <input type="text" class="{{ $inputClass }}"
                            wire:model.live.debounce.300ms="relationSearchQuery.{{ $field->name }}"
                            @focus="open = true" @click="open = true"
                            placeholder="@lang('nexus::translate.chooseRelation')" autocomplete="off">

                        @if(!empty($this->relationSearchResults[$field->name]))
                            <div x-show="open" x-cloak class="absolute z-40 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                                @foreach($this->relationSearchResults[$field->name] as $option)
                                    <button type="button" wire:click="selectRelation('{{ $field->name }}', '{{ $option['id'] }}', '{{ addslashes($option['label']) }}')"
                                        @click="open = false"
                                        class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                        {{ $option['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    @endif

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
