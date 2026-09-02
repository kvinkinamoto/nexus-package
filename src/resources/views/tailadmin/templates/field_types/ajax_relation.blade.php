@php
    $relationName = $field->name;
    $fieldData = $modelSchema['fields'][$relationName];
    $isMultiple = $fieldData->isMultiple ?? false;
    $ajaxRoute = route($fieldData->customData);

    $selectedValue = old($relationName, $model->{$relationName}->id ?? null);
    $selectedValues = old($relationName, $isMultiple ? $model->{$relationName}->pluck('id')->toArray() : []);

    $selectClass = 'h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="mb-4">
    <label for="{{$field->name}}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
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
            @endif {{$tab_lang ?? ''}}
        @endif
    </label>

    @if($field->isDisabledForAction($action ?? null))
        @if($isMultiple && is_array($selectedValues))
            @foreach($selectedValues as $val)
                <input type="hidden" name="{{ $relationName }}[]" value="{{ $val }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $relationName }}" value="{{ $selectedValue }}">
        @endif
    @endif
    <select class="{{ $selectClass }}" id="{{ $relationName }}"
        name="{{ $isMultiple ? $relationName . '[]' : $relationName }}" data-choices data-choices-removeItem
        data-ajax-url="{{ $ajaxRoute }}" data-placeholder="@lang('nexus::translate.chooseRelation')" {{ $isMultiple ? 'multiple="multiple"' : '' }} {{ in_array($relationName, $modelSchema['required']) ? 'required' : '' }} @if($field->isDisabledForAction($action ?? null)) disabled @endif>

        <option value="">
            @lang('nexus::translate.chooseRelation')
        </option>

        @if ($isMultiple && is_array($selectedValues))
            @foreach ($selectedValues as $id)
                <option value="{{ $id }}" selected>{{ $id }}</option>
            @endforeach
        @elseif (!$isMultiple && $selectedValue)
            <option value="{{ $selectedValue }}" selected>{{ $selectedValue }}</option>
        @endif
    </select>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-ajax-url]').forEach(async function (el) {
            const ajaxUrl = el.dataset.ajaxUrl;
            const placeholder = el.dataset.placeholder || '';

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
                searchResultLimit: 20,
                fuseOptions: {
                    includeScore: true
                },
            });

            async function preloadSelectedOptions() {
                const selected = Array.from(el.options)
                    .filter(o => o.selected && o.value)
                    .map(o => o.value);

                if (!selected.length) return;

                try {
                    const res = await fetch(ajaxUrl + '?ids=' + selected.join(','));
                    const data = await res.json();

                    const formatted = (data.data || data).map(item => ({
                        value: item.id,
                        label: item.name,
                        selected: true
                    }));

                    choice.setChoices(formatted, 'value', 'label', true);
                } catch (e) {
                    console.error('Failed to preload selected options:', e);
                }
            }

            try {
                const res = await fetch(ajaxUrl);
                const data = await res.json();

                const formatted = (data.data || data).map(item => ({
                    value: item.id,
                    label: item.name
                }));

                choice.setChoices(formatted, 'value', 'label', false);
            } catch (e) {
                console.error('Initial AJAX fetch failed:', e);
            }

            el.addEventListener('search', async function (event) {
                const searchValue = event.detail.value;

                if (!searchValue || searchValue.length < 2) return;

                try {
                    const res = await fetch(ajaxUrl + '?search=' + encodeURIComponent(searchValue));
                    const data = await res.json();

                    const formatted = (data.data || data).map(item => ({
                        value: item.id,
                        label: item.name
                    }));

                    choice.clearChoices();
                    choice.setChoices(formatted, 'value', 'label', false);
                } catch (e) {
                    console.error('AJAX fetch failed:', e);
                }
            });

            await preloadSelectedOptions();
        });
    });
</script>
