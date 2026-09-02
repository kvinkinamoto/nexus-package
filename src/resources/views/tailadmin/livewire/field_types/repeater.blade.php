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
<div class="mb-4" wire:key="repeater-{{ $field->name }}">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        @if(str_contains($field->label ?? '', '::'))
            @lang($field->label)
        @else
            @lang($moduleNamespace . '::translate.' . Str::lower($field->label ?? $field->name))
        @endif
    </label>

    <div class="custom-scrollbar mb-2 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-100 dark:border-white/5">
                <tr>
                    @foreach($field->repeaterColumns as $column)
                        <th @if($column->width) style="width: {{ $column->width }}" @endif
                            class="px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                            {{ $column->label }}
                        </th>
                    @endforeach
                    <th class="w-8"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse($rows as $index => $row)
                    <tr wire:key="repeater-{{ $field->name }}-row-{{ $row['_rowKey'] ?? $index }}">
                        @foreach($field->repeaterColumns as $column)
                            <td class="p-2 align-top">
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
                        <td class="p-2 align-top">
                            <button type="button" wire:click="removeRepeaterRow('{{ $field->name }}', {{ $index }})"
                                class="flex h-7 w-7 items-center justify-center rounded-md text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10">
                                &times;
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($field->repeaterColumns) + 1 }}" class="px-3 py-3 text-gray-400">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <button type="button" wire:click="addRepeaterRow('{{ $field->name }}')"
        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
        @lang('nexus::translate.add')
    </button>

    @error("relationRows.{$field->name}")
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
