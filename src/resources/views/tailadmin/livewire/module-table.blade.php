{{--
    Reactive replacement for pages/index.blade.php's table body, rendered by
    Nodex\Nexus\Livewire\ModuleTable for any #[Module(livewire: true)] module.
    Filters, sorting, pagination and bulk actions update in place via
    wire:model/wire:click instead of window.location.href / form-submit
    full-reloads. Per-row single-record actions (see the $tableData['actions']
    loop below) mirror pages/tableRow.blade.php: edit either links straight to
    editLivewire.blade.php, or — for a #[Module(slideOver: true)] module —
    opens ModuleForm inline in a slide-over panel instead of
    nexus-slideover.js's <iframe>; delete/restore/duplicate/deletePermanent
    run through ModuleTable::runAction().
--}}
<div>
    @if(!empty($module->config->table->actionGroup))
        @php $activeGroupActions = collect($module->config->table->actionGroup)->filter(fn ($a) => $a->isActive)->values(); @endphp
        @if($activeGroupActions->isNotEmpty())
            {{--
                A genuine docked sidebar (full viewport height, flush to the
                right edge) rather than a floating card mid-screen — that
                first version visually looked like it popped out of the
                middle of the table instead of belonging to the page chrome.
                Slides in once any row is checked, rather than a toolbar
                button/dropdown the user has to notice and open — every
                available bulk action is listed at once, so this scales to
                any number of them without further UI changes. x-show reads
                $wire.selected directly (Alpine's reactive proxy onto the
                Livewire property) so the slide transition actually animates;
                a plain server-rendered @if would just pop in/out with each
                Livewire diff instead.
            --}}
            <div x-data
                x-show="$wire.selected.length > 0"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                x-cloak
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 p-4 dark:border-white/5">
                    <span class="text-sm font-medium text-gray-800 dark:text-white/90">
                        {{ count($selected) }} @lang('nexus::translate.selected')
                    </span>
                    <button type="button" wire:click="$set('selected', [])"
                        class="flex h-6 w-6 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5">
                        <i class="bx bx-x text-lg"></i>
                    </button>
                </div>
                <div class="flex flex-col gap-1 p-3">
                    @foreach($activeGroupActions as $groupAction)
                        <button type="button"
                            wire:click="runGroupAction('{{ $groupAction->name }}')"
                            @if($groupAction->confirm) wire:confirm="@lang('nexus::translate.' . $groupAction->name)?" @endif
                            class="flex items-center rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                            @lang('nexus::translate.' . $groupAction->name)
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if(!empty($tableData['lenses']))
        <div class="mb-4 flex gap-1 border-b border-gray-200 dark:border-gray-800">
            <a href="javascript:void(0);" wire:click="selectLens(null)"
                class="border-b-2 px-3 py-2 text-sm font-medium {{ empty($tableData['activeLens']) ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                @lang('nexus::translate.lens_all')
                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ $tableData['lensCounts']['__all__'] ?? 0 }}</span>
            </a>
            @foreach($tableData['lenses'] as $lensName => $lensDef)
                <a href="javascript:void(0);" wire:click="selectLens('{{ $lensName }}')"
                    class="border-b-2 px-3 py-2 text-sm font-medium {{ $tableData['activeLens'] === $lensName ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    @if($lensDef->icon)
                        <i class="{{ nexus_icon($lensDef->icon, $module->name, 'default_icon') }} align-middle me-1"></i>
                    @endif
                    @lang(Str::lcfirst($module->name) . '::translate.' . $lensDef->label)
                    <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ $tableData['lensCounts'][$lensName] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]">
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                @foreach($tableData['filters'] ?? [] as $filterCfg)
                    @if(($filterCfg->type ?? null) === 'search')
                        <div class="relative w-full max-w-70">
                            <i class="{{ nexus_icon('search') }} pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text"
                                wire:model.live.debounce.400ms="filter.{{ $filterCfg->name }}"
                                wire:key="filter-{{ $filterCfg->name }}"
                                placeholder="{{ nexus_trans_label($module->name, $filterCfg->label ?? null, $filterCfg->name) }}"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-9 pr-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90">
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                @if(!empty($module->config->table->imports))
                    <span id="nexusImportStatus-{{ $module->name }}" class="text-xs text-gray-400"></span>
                    <input type="file" id="nexusImportInput-{{ $module->name }}" accept=".csv,text/csv" class="hidden">
                    <button type="button" onclick="document.getElementById('nexusImportInput-{{ $module->name }}').click()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                        <i class="bx bx-upload"></i>
                        @lang('nexus::translate.Import')
                    </button>
                @endif
                @if(!empty($module->config->table->exports))
                    <button type="button" wire:click="exportTable" wire:loading.attr="disabled" wire:target="exportTable"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                        <i class="bx bx-download"></i>
                        <span id="nexusExportStatus-{{ $module->name }}">@lang('nexus::translate.Export')</span>
                    </button>
                @endif
                @if(!empty($module->config->settings))
                    <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'settings']) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                        <i class="{{ nexus_icon('tools') }}"></i>
                        @lang('nexus::translate.Settings')
                    </a>
                @endif
                @if(!empty($tableData['allColumns']) && count($tableData['allColumns']) > 1)
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.outside="open = false"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                            <i class="bx bx-columns"></i>
                            @lang('nexus::translate.columns')
                        </button>
                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 z-40 mt-2 w-56 rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                            @php $visibleColumnNames = collect($tableData['columns'])->map(fn ($c) => $c->name ?? $c['name'])->all(); @endphp
                            @foreach($tableData['allColumns'] as $col)
                                @php $colName = $col->name ?? $col['name']; @endphp
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                                    <input type="checkbox" wire:click="toggleColumnVisibility('{{ $colName }}')"
                                        @checked(in_array($colName, $visibleColumnNames, true))
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                                    {{ nexus_trans_label($module->name, $col->label ?? null, $colName) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                @foreach($module->config->table->mainActions ?? [] as $mainAction)
                    @if($mainAction->isActive)
                        <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => $mainAction->name]) }}"
                            title="@lang('nexus::translate.' . $mainAction->label)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                            <i class="{{ nexus_icon('plus') }}"></i>
                            @lang('nexus::translate.' . $mainAction->label)
                        </a>
                    @endif
                @endforeach
                {{--
                    Not inside the @if($selected) block below on purpose —
                    ModuleTable::dispatchAsyncBulkAction() clears $selected
                    immediately after dispatching the job (nothing left to
                    act on), so a status shown only while rows are selected
                    would disappear right as the job starts.
                --}}
                <span id="nexusBulkStatus-{{ $module->name }}" class="text-xs text-gray-400"></span>
            </div>
        </div>

        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-gray-100 dark:border-white/5">
                    <tr>
                        @if(!empty($module->config->table->actionGroup))
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" wire:click="toggleSelectAll($event.target.checked)"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            </th>
                        @endif
                        @foreach ($tableData['columns'] ?? [] as $column)
                            <th @if($column->sortable ?? false) wire:click="sortBy('{{ $column->name }}')" @endif
                                class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-gray-500 {{ ($column->sortable ?? false) ? 'cursor-pointer select-none' : '' }} dark:text-gray-400">
                                {{ nexus_trans_label($module->name, $column->label ?? null, $column->name) }}
                                @if($column->sortable ?? false)
                                    @php
                                        $isActiveSort = $sort === $column->name || $sort === '-' . $column->name;
                                        $isDesc = $sort === '-' . $column->name;
                                    @endphp
                                    <i class="bx bx-chevron-down text-xs {{ $isActiveSort ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300' }}"
                                        style="{{ $isActiveSort && $isDesc ? '' : 'transform: rotate(180deg); display:inline-block;' }}"></i>
                                @endif
                            </th>
                        @endforeach
                        @if(!empty($tableData['actions']))
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Actions
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($tableData['data'] as $item)
                        <tr wire:key="row-{{ $item->id }}" class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            @if(!empty($module->config->table->actionGroup))
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected"
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                </td>
                            @endif
                            @foreach ($tableData['columns'] as $column)
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    @if(($column->action ?? null) === 'boolToggle')
                                        <label class="relative inline-flex cursor-pointer items-center">
                                            <input type="checkbox" class="peer sr-only"
                                                @checked($item->{$column->fieldName ?? $column->name})
                                                wire:click="toggleBool('{{ $item->id }}', '{{ $column->fieldName ?? $column->name }}')"
                                                @if($column->actionConfirm ?? false) wire:confirm="@lang('nexus::translate.are_u_sure')" @endif>
                                            <div class="h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700"></div>
                                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></div>
                                        </label>
                                    @elseif(isset($column->action))
                                        <form method="POST"
                                            onsubmit="return sendFormConfirm({{ $column->actionConfirm ?? false }}, '{{ $column->action }}')"
                                            action="{{ route('nexus.module.action', [$module->name, $column->action, 'id' => $item->id]) }}">
                                            @csrf
                                            @if (View::exists(Str::lcfirst($module->name) . '::admin.actions.' . $column->action))
                                                @include(Str::lcfirst($module->name) . '::admin.actions.' . $column->action, ['action' => $column->action, 'fieldName' => $column->name])
                                            @else
                                                @include('nexus::' . config('nexus.template') . '.templates.actions.' . $column->action, ['action' => $column->action, 'fieldName' => $column->name])
                                            @endif
                                            <input type="hidden" name="model_id" value="{{ $item->id }}">
                                        </form>
                                    @elseif(isset($column->customField))
                                        @if (View::exists(Str::lcfirst($module->name) . '::admin.custom_index_fields.' . $column->customField))
                                            @include(Str::lcfirst($module->name) . '::admin.custom_index_fields.' . $column->customField, ['fieldName' => $column->fieldName ?? $column->name])
                                        @else
                                            @include('nexus::' . config('nexus.template') . '.templates.custom_index_fields.' . $column->customField, ['fieldName' => $column->fieldName ?? $column->name])
                                        @endif
                                    @else
                                        @php
                                            $__nexusColumnRelation = $module->config->relations->is_available[$column->name] ?? null;
                                            $__nexusColumnValue = $item->{$column->name} ?? null;
                                            $__nexusColumnDisplay = nexus_enum_display($__nexusColumnValue, $module->name);
                                        @endphp
                                        @if($__nexusColumnRelation && $__nexusColumnValue instanceof \Illuminate\Database\Eloquent\Model)
                                            {{ app(\Nodex\Nexus\Services\RelationService::class)->formatLabel($__nexusColumnValue, $__nexusColumnRelation->showField, $__nexusColumnRelation->showFieldFallback) }}
                                        @else
                                            {{ $__nexusColumnDisplay }}
                                        @endif
                                        @if(isset($item->depth) && $item->depth > 0 && $column->name == 'id')
                                            <span class="text-orange-500">|{{ str_repeat('_', (int) $item->depth) }} </span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            @if(!empty($tableData['actions']))
                                @php
                                    $isSoftDeletable = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($item));
                                    $isDeleted = $isSoftDeletable ? $item->trashed() : false;
                                @endphp
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @foreach ($tableData['actions'] as $rowAction)
                                            @if(!$rowAction->isActive)
                                                @continue
                                            @endif
                                            @if(
                                                ($rowAction->name === 'delete' && $isDeleted) ||
                                                ($rowAction->name === 'edit' && $isDeleted) ||
                                                ($rowAction->name === 'restore' && !$isDeleted) ||
                                                ($rowAction->name === 'deletePermanent' && !$isDeleted)
                                            )
                                                @continue
                                            @endif
                                            @php
                                                $isMutatingAction = in_array($rowAction->name, ['delete', 'restore', 'deletePermanent', 'duplicate'], true);
                                                $rowBtnClass = 'flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5';
                                            @endphp
                                            @if($rowAction->name === 'edit' && ($module->config->slideOver ?? false))
                                                <button type="button" title="{{ $rowAction->label }}"
                                                    wire:click="openSlideOver('{{ $item->id }}')" class="{{ $rowBtnClass }}">
                                                    <i class="{{ nexus_icon($rowAction->icon, $module->name, 'default_icon') }}"></i>
                                                </button>
                                            @elseif($isMutatingAction)
                                                <button type="button" title="{{ $rowAction->label }}"
                                                    wire:click="runAction('{{ $rowAction->name }}', '{{ $item->id }}')"
                                                    @if($rowAction->confirm ?? false) wire:confirm="@lang('nexus::translate.are_u_sure')" @endif
                                                    class="{{ $rowBtnClass }}">
                                                    <i class="{{ nexus_icon($rowAction->icon, $module->name, 'default_icon') }}"></i>
                                                </button>
                                            @else
                                                {{--
                                                    Any #[TableAction] not in the small $isMutatingAction
                                                    whitelist above (edit/view/a custom action name) has no
                                                    ModuleTable::runAction() handler to call via Livewire, so
                                                    it stays a plain full-page link to NexusController::action()
                                                    — but that meant $rowAction->confirm was silently ignored
                                                    here even though it's real and honored on the Livewire
                                                    branch (wire:confirm). sendFormConfirm() (defined in
                                                    pages/indexLivewire.blade.php, this table's page wrapper)
                                                    is the same plain-onclick-confirm idiom already used for
                                                    #[Column(action:)] row buttons just above.
                                                --}}
                                                <a href="{{ route('nexus.module.action', [$module->name, $rowAction->name, 'id' => $item->id]) }}"
                                                    title="{{ $rowAction->label }}" class="{{ $rowBtnClass }}"
                                                    @if($rowAction->confirm ?? false)
                                                        onclick="return sendFormConfirm(true, '{{ $rowAction->name }}', @js(__('nexus::translate.are_u_sure')))"
                                                    @endif>
                                                    <i class="{{ nexus_icon($rowAction->icon, $module->name, 'default_icon') }}"></i>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="99" class="px-4 py-10 text-center text-sm text-gray-400">@lang('nexus::translate.no_results_found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tableData['data']->total() > 0)
            <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-800">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $tableData['data']->firstItem() }}&ndash;{{ $tableData['data']->lastItem() }} / {{ $tableData['data']->total() }}
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" wire:key="page-prev"
                        @disabled($tableData['data']->currentPage() <= 1)
                        wire:click="gotoPage({{ $tableData['data']->currentPage() - 1 }})"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/5">&laquo;</button>
                    @for ($p = 1; $p <= $tableData['data']->lastPage(); $p++)
                        <button type="button" wire:key="page-{{ $p }}"
                            wire:click="gotoPage({{ $p }})"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-sm {{ $p === $tableData['data']->currentPage() ? 'bg-brand-500 text-white' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">{{ $p }}</button>
                    @endfor
                    <button type="button" wire:key="page-next"
                        @disabled($tableData['data']->currentPage() >= $tableData['data']->lastPage())
                        wire:click="gotoPage({{ $tableData['data']->currentPage() + 1 }})"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/5">&raquo;</button>
                </div>
            </div>
        @endif
    </div>

    @if($module->config->slideOver ?? false)
        {{--
            Panel visibility is driven purely by $slideOverOpen (server-side
            Livewire state), same as the old Bootstrap-Offcanvas-free version
            — just Tailwind transition classes instead of Bootstrap's
            .offcanvas/.show CSS for the slide.
        --}}
        <div wire:key="slideover-panel" class="fixed inset-0 z-99999 {{ $slideOverOpen ? '' : 'pointer-events-none' }}">
            <div wire:click="closeSlideOver" wire:key="slideover-backdrop"
                class="absolute inset-0 bg-gray-900/50 transition-opacity duration-300 {{ $slideOverOpen ? 'opacity-100' : 'opacity-0' }}"></div>
            <div x-on:keydown.escape.window="$wire.closeSlideOver()"
                class="absolute right-0 top-0 h-full w-full max-w-180 transform bg-white shadow-theme-xl transition-transform duration-300 dark:bg-gray-900 {{ $slideOverOpen ? 'translate-x-0' : 'translate-x-full' }}">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">@lang('nexus::translate.edit')</h5>
                    <button type="button" wire:click="closeSlideOver"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="custom-scrollbar h-[calc(100%-64px)] overflow-y-auto p-5">
                    @if($slideOverOpen && $slideOverId)
                        @livewire('nexus-module-form', ['moduleName' => $module->name, 'id' => $slideOverId, 'embedded' => true], key('slideover-form-' . $slideOverId))
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
