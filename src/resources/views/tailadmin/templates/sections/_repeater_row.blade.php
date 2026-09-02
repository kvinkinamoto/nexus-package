{{--
    One row of a 'repeater' field (see field_types/repeater.blade.php).
    Reused for both real rows and the JS <template> clone (index = '__INDEX__').

    Expects: $relationName, $columns (RepeaterFieldConfigDto[]), $index, $row (array), $module. Optional: $action.
--}}
<tr class="nexus-repeater-row border-b border-gray-100 dark:border-white/5">
    @if(is_array($row) && !empty($row['id']))
        <input type="hidden" name="relation[{{ $relationName }}][{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif
    @foreach($columns as $column)
        <td class="p-2 align-top">
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
    <td class="p-2 align-top">
        <button type="button" class="nexus-repeater-remove rounded-lg border border-error-200 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-800 dark:hover:bg-error-500/10">
            @lang('nexus::translate.remove')
        </button>
    </td>
</tr>
