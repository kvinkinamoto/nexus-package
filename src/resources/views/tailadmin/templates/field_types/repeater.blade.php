{{--
    Generic repeater field: a dynamic table of related rows, backed by the
    same wire format (and the same StoreRelationActionMethod::saveMultipleRelation()
    HasMany branch) as the hand-written per-module repeater views this
    generalizes — relation[{relationName}][{index}][{columnName}], with a
    hidden [id] input on existing rows. No server-side changes were needed
    to introduce this field type.

    Columns come from #[RepeaterField] stacked on the relation method (see
    Attributes/RepeaterField.php) — $field->repeaterColumns, an array of
    RepeaterFieldConfigDto in declaration order. Each cell renders through
    the same dispatch as any other field (_field.blade.php: module override
    -> registry -> built-in -> unknown), so a column can be any registered
    type.

    Not yet implemented: per-column #[RepeaterField(showWhen:)] — accepted
    and stored on the column DTO, but not evaluated here. Phase 4.2's
    form-wide conditional-fields runtime assumes one global set of field
    names; a repeater row's fields are dynamically indexed
    (relation[x][0][y], relation[x][1][y], ...), so a per-row-scoped
    variant is a separate, not-yet-built mechanism.
--}}
@php
    $__nexusRepeaterRelation = $field->name;
    $__nexusRepeaterColumns = $field->repeaterColumns ?? [];
    $__nexusRepeaterRows = old('relation.' . $__nexusRepeaterRelation);
    if ($__nexusRepeaterRows === null) {
        $__nexusRepeaterRows = (isset($model) && $model) ? $model->{$__nexusRepeaterRelation}->toArray() : [];
    }
@endphp
<div class="nexus-repeater mb-4" data-nexus-repeater="{{ $__nexusRepeaterRelation }}">
    @if(!empty($field->label))
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        </label>
    @endif

    <div class="custom-scrollbar mb-2 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
        <table class="nexus-repeater-table w-full text-left text-sm">
            <thead class="border-b border-gray-100 dark:border-white/5">
                <tr>
                    @foreach($__nexusRepeaterColumns as $column)
                        <th @if($column->width) style="width: {{ $column->width }}" @endif
                            class="px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                            @if($column->label && str_contains($column->label, '::'))
                                @lang($column->label)
                            @else
                                @lang(lcfirst($module->name) . '::translate.' . ($column->label ?? $column->name))
                            @endif
                            @if($column->required) * @endif
                        </th>
                    @endforeach
                    <th class="w-15"></th>
                </tr>
            </thead>
            <tbody class="nexus-repeater-rows divide-y divide-gray-100 dark:divide-white/5">
                @foreach($__nexusRepeaterRows as $__nexusIndex => $__nexusRow)
                    @include('nexus::'. config('nexus.template').'.templates.sections._repeater_row', [
                        'relationName' => $__nexusRepeaterRelation,
                        'columns' => $__nexusRepeaterColumns,
                        'index' => $__nexusIndex,
                        'row' => $__nexusRow,
                        'module' => $module,
                        'action' => $action ?? null,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>

    <button type="button" class="nexus-repeater-add rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" data-nexus-repeater-target="{{ $__nexusRepeaterRelation }}">
        @lang('nexus::translate.add')
    </button>

    <template class="nexus-repeater-template" data-nexus-repeater-template="{{ $__nexusRepeaterRelation }}">
        @include('nexus::'. config('nexus.template').'.templates.sections._repeater_row', [
            'relationName' => $__nexusRepeaterRelation,
            'columns' => $__nexusRepeaterColumns,
            'index' => '__INDEX__',
            'row' => [],
            'module' => $module,
            'action' => $action ?? null,
        ])
    </template>
</div>
