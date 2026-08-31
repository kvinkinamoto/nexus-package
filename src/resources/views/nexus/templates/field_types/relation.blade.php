@php
    $relationName = $field->name;
    $relationData = $model->{$relationName} ?? null;
    $relationsConfig = $module?->relations?->is_available[$relationName];

    $isMultiple = false;
    if ($relationsConfig && isset($relationsConfig->type)
       && ($relationsConfig->type == \Nodex\Nexus\Enums\RelationConfigParamsEnum::BELONGS_TO_MANY->value
       || $relationsConfig->type == \Nodex\Nexus\Enums\RelationConfigParamsEnum::HAS_MANY->value)
    ) {
         $isMultiple = true;
    }

    $isRequired = false;
    if ($relationsConfig && isset($relationsConfig->isRequired) && $relationsConfig->isRequired == true) {
       $isRequired = true;
    }

    $ajaxConfig = $relationsConfig->ajaxConfig ?? null;
    $isAjax = $ajaxConfig?->isAjax ?? false;
    $ajaxMode = $ajaxConfig?->mode ?? 'search';
    $routeName = $ajaxConfig?->route ?? 'nexus.relation-search';
    $ajaxUrl = $isAjax ? route($routeName, ['module' => $module->name, 'relation' => $relationName]) : null;
@endphp

<div class="mb-3">
    <label for="{{ $relationName }}" class="form-label">
        @if(isset($field->label) && !empty($field->label))
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        @else
            @if(str_contains($field->name, '::'))
                @lang($field->name)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->name)
            @endif {{ $tab_lang ?? '' }}
        @endif

        @if($isRequired) * @endif
    </label>

    @if($field->isDisabledForAction($action ?? null))
        @php
            $currentRelationValue = old("relation.{$relationName}");
            if ($currentRelationValue === null && isset($model) && $model->exists) {
                $currentRelationValue = $isMultiple ? $model->{$relationName}->pluck('id')->toArray() : $model->{$relationName}?->id;
            }
        @endphp
        @if($isMultiple && is_array($currentRelationValue))
            @foreach($currentRelationValue as $val)
                <input type="hidden" name="relation[{{ $relationName }}][]" value="{{ $val }}">
            @endforeach
        @else
            <input type="hidden" name="relation[{{ $relationName }}]" value="{{ $currentRelationValue }}">
        @endif
    @endif
    <select class="form-control"
            data-choices
            data-choices-removeItem
            id="{{ $relationName }}"
            @if($isRequired) required @endif
            @if($isMultiple) multiple="multiple" @endif
            @if($isMultiple) name="relation[{{ $relationName }}][]"
            @else name="relation[{{ $relationName }}]" @endif
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            @if($isAjax)
                data-ajax-url="{{ $ajaxUrl }}"
                data-ajax-mode="{{ $ajaxMode }}"
            @endif
    >
        <option value="">
            @lang('nexus::translate.chooseRelation')
        </option>
        @foreach($formData['relatedData'][$relationName] ?? [] as $key => $value)
            <option value="{{ $value->id }}"
                    @php
                        $oldValue = old("relation.{$relationName}");
                        $isSelected = false;
                        if ($oldValue !== null) {
                            $isSelected = $isMultiple ? in_array($value->id, (array)$oldValue) : ($value->id == $oldValue);
                        } elseif (isset($model) && $model->exists) {
                            $isSelected = $isMultiple ? $model->{$relationName}->contains($value->id) : ($model->{$relationName}?->id == $value->id);
                        }
                    @endphp
                    @if($isSelected) selected="selected" @endif
            >
                @if(isset($relationData['depth']))
                    |@for($i = 0; $i < $relationData['depth'][$value->id]; $i++)
                        {{ '_' }}
                    @endfor
                @endif
                {{ $value->label ?? $value->{$relationsConfig->showField} ?? $value->id }}
            </option>
        @endforeach
    </select>
</div>

@if($isAjax)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('{{ $relationName }}');
        if (!el) return;

        const ajaxUrl = el.dataset.ajaxUrl;
        const ajaxMode = el.dataset.ajaxMode;
        const placeholder = "@lang('nexus::translate.chooseRelation')";

        const choice = new Choices(el, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: placeholder,
            searchPlaceholderValue: placeholder,
            searchEnabled: true,
            shouldSort: false,
            duplicateItemsAllowed: false,
            loadingText: 'Loading...',
            noResultsText: 'No results found',
            itemSelectText: '',
            searchResultLimit: 50,
        });

        if (ajaxMode === 'load') {
            // Загрузка перших результатів зразу після загрузки сторінки
            fetch(ajaxUrl)
                .then(res => res.json())
                .then(data => {
                    let results = data.results || data;
                    if (results && results.data && Array.isArray(results.data)) {
                        results = results.data;
                    }
                    
                    const existingValues = Array.from(el.options).map(opt => String(opt.value));
                    const formatted = (Array.isArray(results) ? results : [])
                        .filter(item => !existingValues.includes(String(item.id)))
                        .map(item => ({
                            value: item.id,
                            label: item.label,
                            selected: false
                        }));
                    
                    // Додаємо нові варіанти до існуючих (вибраних), не замінюючи їх
                    choice.setChoices(formatted, 'value', 'label', false);
                })
                .catch(e => console.error('Initial AJAX fetch failed:', e));
        }

        // Завжди вмикаємо пошук через аякс, якщо це аякс-поле
        let searchTimeout = null;
        el.addEventListener('search', function (event) {
            const searchValue = event.detail.value;
            if (!searchValue || searchValue.length < 2) return;

            if (searchTimeout) clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                fetch(ajaxUrl + '?q=' + encodeURIComponent(searchValue))
                    .then(res => res.json())
                    .then(data => {
                        let results = data.results || data;
                        if (results && results.data && Array.isArray(results.data)) {
                            results = results.data;
                        }

                        const existingValues = Array.from(el.options).map(opt => String(opt.value));
                        const formatted = (Array.isArray(results) ? results : [])
                            .filter(item => !existingValues.includes(String(item.id)))
                            .map(item => ({
                                value: String(item.id),
                                label: item.label
                            }));

                        // При пошуку ми очищаємо список варіантів (крім вибраних) і показуємо лише знайдені
                        choice.clearChoices();
                        choice.setChoices(formatted, 'value', 'label', false);
                    })
                    .catch(e => console.error('AJAX search failed:', e));
            }, 300);
        });
    });
</script>
@endif
