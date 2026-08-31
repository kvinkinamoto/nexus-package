{{--
    Native Livewire repeater — reactive replacement for nexus-repeater.js's
    client-side <template> clone. Row state lives in
    Nodex\Nexus\Livewire\ModuleForm::$relationRows[{{ $field->name }}],
    shaped exactly like relation[{relation}][{index}][{column}] so
    StoreRelationActionMethod::saveMultipleRelation() needs no changes.

    Per-cell dispatch follows the same "module override wins" convention as
    the legacy FieldTypeRegistry: a module can drop a
    {module}::admin.livewire_field_types.{type}.blade.php to render a
    RepeaterField column type Livewire doesn't know natively (e.g. Cart's
    cartProductSelect ajax product picker) without this package file
    knowing anything module-specific.
--}}
@php
    $moduleNamespace = \Illuminate\Support\Str::lcfirst($module->name);
    $rows = $this->relationRows[$field->name] ?? [];
@endphp
<div class="mb-3" wire:key="repeater-{{ $field->name }}">
    <label class="form-label">
        @if(str_contains($field->label ?? '', '::'))
            @lang($field->label)
        @else
            @lang($moduleNamespace . '::translate.' . Str::lower($field->label ?? $field->name))
        @endif
    </label>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-2">
            <thead>
                <tr>
                    @foreach($field->repeaterColumns as $column)
                        <th @if($column->width) style="width: {{ $column->width }}" @endif>
                            {{ $column->label }}
                        </th>
                    @endforeach
                    <th style="width: 1%"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr wire:key="repeater-{{ $field->name }}-row-{{ $row['_rowKey'] ?? $index }}">
                        @foreach($field->repeaterColumns as $column)
                            <td>
                                @includeFirst([
                                    $moduleNamespace . '::admin.livewire_field_types.' . $column->type,
                                    'nexus::' . config('nexus.template') . '.livewire.field_types.repeater_cells.' . $column->type,
                                    'nexus::' . config('nexus.template') . '.livewire.field_types.repeater_cells.unsupported',
                                ], [
                                    'field' => $field,
                                    'column' => $column,
                                    'index' => $index,
                                    'row' => $row,
                                ])
                            </td>
                        @endforeach
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click="removeRepeaterRow('{{ $field->name }}', {{ $index }})">
                                &times;
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($field->repeaterColumns) + 1 }}" class="text-muted">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addRepeaterRow('{{ $field->name }}')">
        @lang('nexus::translate.add')
    </button>

    @error("relationRows.{$field->name}")
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
