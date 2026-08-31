{{--
    One row of a 'repeater' field (see field_types/repeater.blade.php).
    Reused for both real rows and the JS <template> clone (index = '__INDEX__').

    Expects: $relationName, $columns (RepeaterFieldConfigDto[]), $index, $row (array), $module. Optional: $action.
--}}
<tr class="nexus-repeater-row">
    @if(is_array($row) && !empty($row['id']))
        <input type="hidden" name="relation[{{ $relationName }}][{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif
    @foreach($columns as $column)
        <td>
            @php
                $__nexusCellField = new \Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto(
                    name: "relation[{$relationName}][{$index}][{$column->name}]",
                    type: $column->type,
                    label: $column->label,
                    default: is_array($row) ? ($row[$column->name] ?? null) : null,
                    isRequired: $column->required,
                );
            @endphp
            @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                'field' => $__nexusCellField,
                'module' => $module,
                'model' => null,
                'action' => $action ?? null,
            ])
        </td>
    @endforeach
    <td>
        <button type="button" class="btn btn-sm btn-soft-danger nexus-repeater-remove">
            @lang('nexus::translate.remove')
        </button>
    </td>
</tr>
