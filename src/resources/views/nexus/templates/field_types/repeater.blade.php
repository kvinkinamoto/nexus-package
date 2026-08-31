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
<div class="mb-3 nexus-repeater" data-nexus-repeater="{{ $__nexusRepeaterRelation }}">
    @if(!empty($field->label))
        <label class="form-label">
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        </label>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered align-middle nexus-repeater-table">
            <thead>
                <tr>
                    @foreach($__nexusRepeaterColumns as $column)
                        <th @if($column->width) style="width: {{ $column->width }}" @endif>
                            @if($column->label && str_contains($column->label, '::'))
                                @lang($column->label)
                            @else
                                @lang(lcfirst($module->name) . '::translate.' . ($column->label ?? $column->name))
                            @endif
                            @if($column->required) * @endif
                        </th>
                    @endforeach
                    <th style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody class="nexus-repeater-rows">
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

    <button type="button" class="btn btn-sm btn-soft-primary nexus-repeater-add" data-nexus-repeater-target="{{ $__nexusRepeaterRelation }}">
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
