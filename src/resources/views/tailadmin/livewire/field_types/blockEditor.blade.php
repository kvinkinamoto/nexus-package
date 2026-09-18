{{--
    #[Field(type: 'blockEditor')] — an ordered list of heterogeneous rows,
    each row a different BlockTypeRegistry entry with its own field set.
    Sibling to field_types/repeater.blade.php, not an extension of it: that
    view's header/body both loop one fixed $field->repeaterColumns array
    shared by every row, which can't express "row 0 is a Hero, row 1 is a
    Text block" — see ManagesBlockFields' docblock. Row state still lives in
    Nodex\Nexus\Livewire\ModuleForm::$relationRows[{{ $field->name }}], same
    container the repeater uses, just shaped
    ['id'=>..,'_rowKey'=>..,'type'=>..,'data'=>[...]] instead of one entry
    per repeaterColumn — so buildLegacyInput()/saveMultipleRelation() need no
    changes to persist it.

    No per-block-field module-override hook (unlike repeater cells' {module}::
    admin.livewire_field_types.{type} check) — only the built-in
    block_cells/{type}.blade.php convention, falling back to
    block_cells/unsupported. Kept simple deliberately for v1; a module/plugin
    can still add a whole new BLOCK TYPE via BlockTypeRegistry::register().
--}}
@php
    $moduleNamespace = \Illuminate\Support\Str::lcfirst($module->name);
    $rows = $this->relationRows[$field->name] ?? [];
    $__nexusBlockRegistry = app(\Nodex\Nexus\Services\Blocks\BlockTypeRegistry::class);
@endphp
<div class="mb-4" wire:key="blockEditor-{{ $field->name }}">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        @if(str_contains($field->label ?? '', '::'))
            @lang($field->label)
        @else
            @lang($moduleNamespace . '::translate.' . Str::lower($field->label ?? $field->name))
        @endif
    </label>

    <div class="space-y-3">
        @forelse($rows as $index => $row)
            @php $__nexusBlockType = $__nexusBlockRegistry->find($row['type'] ?? null); @endphp
            <div class="rounded-lg border border-gray-200 dark:border-gray-800" wire:key="block-{{ $field->name }}-{{ $row['_rowKey'] ?? $index }}">
                <div class="flex items-center justify-between rounded-t-lg border-b border-gray-100 bg-gray-50 px-3 py-2 dark:border-white/5 dark:bg-white/5">
                    <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                        {{ $__nexusBlockType?->label() ?? ($row['type'] ?? '?') }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="moveBlockUp('{{ $field->name }}', {{ $index }})"
                            @disabled($index === 0)
                            class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-white/10">
                            &uarr;
                        </button>
                        <button type="button" wire:click="moveBlockDown('{{ $field->name }}', {{ $index }})"
                            @disabled($index === count($rows) - 1)
                            class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-white/10">
                            &darr;
                        </button>
                        <button type="button" wire:click="removeBlock('{{ $field->name }}', {{ $index }})"
                            class="flex h-7 w-7 items-center justify-center rounded-md text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 p-3">
                    @if($__nexusBlockType)
                        @foreach($__nexusBlockType->fields() as $blockField)
                            @php
                                $__nexusCellBuiltInView = 'nexus::' . config('nexus.template') . '.livewire.field_types.block_cells.' . $blockField->type;
                                $__nexusCellVars = ['field' => $field, 'blockField' => $blockField, 'index' => $index, 'row' => $row];
                            @endphp
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                    {{ $blockField->label }}@if($blockField->required)<span class="text-error-500">*</span>@endif
                                </label>
                                @if(View::exists($__nexusCellBuiltInView))
                                    @include($__nexusCellBuiltInView, $__nexusCellVars)
                                @else
                                    @include('nexus::' . config('nexus.template') . '.livewire.field_types.block_cells.unsupported', $__nexusCellVars)
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-error-500">Unknown block type "{{ $row['type'] ?? '' }}".</p>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">—</p>
        @endforelse
    </div>

    <div class="mt-2 flex flex-wrap gap-2">
        @foreach($__nexusBlockRegistry->all() as $type)
            <button type="button" wire:click="addBlock('{{ $field->name }}', '{{ $type->key() }}')"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                + {{ $type->label() }}
            </button>
        @endforeach
    </div>

    @error("relationRows.{$field->name}")
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
