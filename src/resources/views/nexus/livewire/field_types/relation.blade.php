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
@endphp
<div class="mb-3" wire:key="relation-{{ $field->name }}">
    <label class="form-label">
        @if(str_contains($field->label ?? '', '::'))
            @lang($field->label)
        @else
            @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($field->label ?? $field->name))
        @endif
    </label>

    @if($isMultiple)
        @php
            $selectedIds = (array) ($this->data[$field->name] ?? []);
            $labels = (array) ($this->relationLabels[$field->name] ?? []);
        @endphp
        @if(!empty($selectedIds))
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach($selectedIds as $id)
                    <span class="badge bg-light text-dark border d-flex align-items-center gap-2 p-2">
                        {{ $labels[$id] ?? $id }}
                        @unless($isDisabled)
                            <button type="button" class="btn-close" style="font-size: 0.6rem"
                                wire:click="removeRelationItem('{{ $field->name }}', '{{ $id }}')"
                                aria-label="{{ __('nexus::translate.cancel') }}"></button>
                        @endunless
                    </span>
                @endforeach
            </div>
        @endif

        @unless($isDisabled)
            <div class="position-relative">
                <input type="text" class="form-control @error($errorKey) is-invalid @enderror"
                    wire:model.live.debounce.300ms="relationSearchQuery.{{ $field->name }}"
                    placeholder="@lang('nexus::translate.chooseRelation')" autocomplete="off">

                @if(!empty($this->relationSearchResults[$field->name]))
                    <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 240px; overflow-y: auto;">
                        @foreach($this->relationSearchResults[$field->name] as $option)
                            <button type="button" class="list-group-item list-group-item-action"
                                wire:click="selectRelation('{{ $field->name }}', '{{ $option['id'] }}', '{{ addslashes($option['label']) }}')">
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
            <div class="form-control bg-light">{{ $selectedLabel ?? $selectedId ?? '—' }}</div>
        @else
            <div class="position-relative">
                @if($selectedId)
                    <div class="d-flex align-items-center gap-2 form-control">
                        <span class="flex-grow-1">{{ $selectedLabel ?? $selectedId }}</span>
                        <button type="button" class="btn-close" wire:click="clearRelation('{{ $field->name }}')" aria-label="{{ __('nexus::translate.cancel') }}"></button>
                    </div>
                @else
                    <input type="text" class="form-control @error($errorKey) is-invalid @enderror"
                        wire:model.live.debounce.300ms="relationSearchQuery.{{ $field->name }}"
                        placeholder="@lang('nexus::translate.chooseRelation')" autocomplete="off">

                    @if(!empty($this->relationSearchResults[$field->name]))
                        <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 240px; overflow-y: auto;">
                            @foreach($this->relationSearchResults[$field->name] as $option)
                                <button type="button" class="list-group-item list-group-item-action"
                                    wire:click="selectRelation('{{ $field->name }}', '{{ $option['id'] }}', '{{ addslashes($option['label']) }}')">
                                    {{ $option['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        @endif
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
