{{--
    Native Livewire repeater — reactive replacement for nexus-repeater.js's
    client-side <template> clone. Row state lives in
    Nodex\Nexus\Livewire\ModuleForm::$relationRows[{{ $field->name }}],
    shaped exactly like relation[{relation}][{index}][{column}] so
    StoreRelationActionMethod::saveMultipleRelation() needs no changes.

    Per-cell dispatch mirrors dispatch.blade.php's own resolution order (see
    its docblock) one tier further down — module override (same
    {module}::admin.livewire_field_types.{type}.blade.php a top-level field
    would use, e.g. Cart's cartProductSelect ajax product picker) -> the
    shared FieldTypeRegistry (so a type registered via a module's
    FieldTypes/ folder or the plugin hook renders as a cell too, not just as
    a full field) -> a built-in repeater_cells/{type}.blade.php partial
    (deliberately separate from field_types/{type}.blade.php — a cell needs
    compact markup, not a labeled form group) -> repeater_cells/unsupported.
--}}
@php
    $moduleNamespace = \Illuminate\Support\Str::lcfirst($module->name);
    $rows = $this->relationRows[$field->name] ?? [];
    $__nexusCellRegistry = app(\Nodex\Nexus\Services\FieldTypeRegistry::class);
@endphp
<div class="mb-4" wire:key="repeater-{{ $field->name }}">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        {{ nexus_trans_label($module->name, $field->label ?? null, $field->name) }}
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
                            @php
                                $__nexusCellType = $__nexusCellRegistry->resolveType($column->type);
                                $__nexusCellModuleView = $moduleNamespace . '::admin.livewire_field_types.' . $column->type;
                                $__nexusCellRegisteredView = $__nexusCellRegistry->resolveViewName($__nexusCellType);
                                $__nexusCellBuiltInView = 'nexus::' . config('nexus.template') . '.livewire.field_types.repeater_cells.' . $__nexusCellType;
                                $__nexusCellVars = ['field' => $field, 'column' => $column, 'index' => $index, 'row' => $row];
                            @endphp
                            <td class="p-2 align-top">
                                @if(View::exists($__nexusCellModuleView))
                                    @include($__nexusCellModuleView, $__nexusCellVars)
                                @elseif($__nexusCellRegisteredView)
                                    @include($__nexusCellRegisteredView, $__nexusCellVars)
                                @elseif($__nexusCellRegistry->hasRenderer($__nexusCellType))
                                    {!! $__nexusCellRegistry->render($__nexusCellType, new \Nodex\Nexus\Dto\FieldRenderContext($field, $this->id ? $moduleConfig->model::find($this->id) : new ($moduleConfig->model)(), $module, $action ?? null, null, $row, [])) !!}
                                @elseif(View::exists($__nexusCellBuiltInView))
                                    @include($__nexusCellBuiltInView, $__nexusCellVars)
                                @else
                                    @include('nexus::' . config('nexus.template') . '.livewire.field_types.repeater_cells.unsupported', $__nexusCellVars)
                                @endif
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
