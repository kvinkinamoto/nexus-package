{{--
    Reactive replacement for pages/index.blade.php's table body, rendered by
    Nodex\Nexus\Livewire\ModuleTable for any #[Module(livewire: true)] module.
    Filters, sorting, pagination and bulk actions update in place via
    wire:model/wire:click instead of window.location.href / form-submit
    full-reloads. Per-row single-record actions (see the $tableData['actions']
    loop below) mirror pages/tableRow.blade.php: edit either links straight to
    editLivewire.blade.php, or — for a #[Module(slideOver: true)] module —
    opens ModuleForm inline in an offcanvas panel (Livewire Етап 5) instead of
    nexus-slideover.js's <iframe>; delete/restore/duplicate/deletePermanent
    run through ModuleTable::runAction().
--}}
<div>
    @if(!empty($tableData['lenses']))
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a href="javascript:void(0);" wire:click="selectLens(null)"
                   class="nav-link {{ empty($tableData['activeLens']) ? 'active' : '' }}">
                    @lang('nexus::translate.lens_all')
                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $tableData['lensCounts']['__all__'] ?? 0 }}</span>
                </a>
            </li>
            @foreach($tableData['lenses'] as $lensName => $lensDef)
                <li class="nav-item">
                    <a href="javascript:void(0);" wire:click="selectLens('{{ $lensName }}')"
                       class="nav-link {{ $tableData['activeLens'] === $lensName ? 'active' : '' }}">
                        @if($lensDef->icon)
                            <i class="{{ nexus_icon($lensDef->icon, $module->name, 'default_icon') }} align-middle me-1"></i>
                        @endif
                        @lang(Str::lcfirst($module->name) . '::translate.' . $lensDef->label)
                        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $tableData['lensCounts'][$lensName] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-sm-6">
                    @foreach($tableData['filters'] ?? [] as $filterCfg)
                        @if(($filterCfg->type ?? null) === 'search')
                            <input type="text" class="form-control" style="max-width: 280px"
                                wire:model.live.debounce.400ms="filter.{{ $filterCfg->name }}"
                                wire:key="filter-{{ $filterCfg->name }}"
                                placeholder="@lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($filterCfg->label ?? $filterCfg->name))">
                        @endif
                    @endforeach
                </div>
                <div class="col-sm-6 text-sm-end">
                    @foreach($module->config->table->mainActions ?? [] as $mainAction)
                        @if($mainAction->isActive)
                            <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => $mainAction->name]) }}"
                                class="btn btn-success btn-rounded waves-effect waves-light mb-2 me-2"
                                title="@lang('nexus::translate.' . $mainAction->label)">
                                <i class="{{ nexus_icon('plus') }} me-1"></i>
                                @lang('nexus::translate.' . $mainAction->label)
                            </a>
                        @endif
                    @endforeach
                    @if(!empty($selected) && !empty($module->config->table->actionGroup))
                        @foreach($module->config->table->actionGroup as $groupAction)
                            @if($groupAction->isActive)
                                <button type="button" class="btn btn-light btn-sm me-1"
                                    wire:click="runGroupAction('{{ $groupAction->name }}')"
                                    @if($groupAction->confirm) wire:confirm="@lang('nexus::translate.' . $groupAction->name)?" @endif>
                                    @lang('nexus::translate.' . $groupAction->name) ({{ count($selected) }})
                                </button>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            @if(!empty($module->config->table->actionGroup))
                                <th style="width: 20px;">
                                    <input type="checkbox" class="form-check-input"
                                        wire:click="toggleSelectAll($event.target.checked)">
                                </th>
                            @endif
                            @foreach ($tableData['columns'] ?? [] as $column)
                                <th @if($column->sortable ?? false) class="cursor-pointer" wire:click="sortBy('{{ $column->name }}')" @endif>
                                    @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($column->label ?? $column->name))
                                    @if($column->sortable ?? false)
                                        @php
                                            $isActiveSort = $sort === $column->name || $sort === '-' . $column->name;
                                            $isDesc = $sort === '-' . $column->name;
                                        @endphp
                                        <i class="{{ nexus_icon('sort') }} sort-icon @if($isActiveSort) text-dark @endif"
                                            style="vertical-align: -2px; @if($isActiveSort && $isDesc) transform: rotate(180deg); @endif"></i>
                                    @endif
                                </th>
                            @endforeach
                            @if(!empty($tableData['actions']))
                                <th class="text-end">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tableData['data'] as $item)
                            <tr wire:key="row-{{ $item->id }}">
                                @if(!empty($module->config->table->actionGroup))
                                    <td>
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </td>
                                @endif
                                @foreach ($tableData['columns'] as $column)
                                    <td>
                                        @if(($column->action ?? null) === 'boolToggle')
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" role="switch"
                                                    @checked($item->{$column->fieldName ?? $column->name})
                                                    wire:click="toggleBool('{{ $item->id }}', '{{ $column->fieldName ?? $column->name }}')"
                                                    @if($column->actionConfirm ?? false) wire:confirm="@lang('nexus::translate.are_u_sure')" @endif>
                                            </div>
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
                                            {{ $item->{$column->name} ?? '' }}
                                            @if(isset($item->depth) && $item->depth > 0 && $column->name == 'id')
                                                <span style="color: #ff6c2f;">|{{ str_repeat('_', (int) $item->depth) }} </span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                                @if(!empty($tableData['actions']))
                                    @php
                                        $isSoftDeletable = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($item));
                                        $isDeleted = $isSoftDeletable ? $item->trashed() : false;
                                    @endphp
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
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
                                                    // Only these mutate the record via a plain redirect response
                                                    // (see ModuleTable::SINGLE_RECORD_ACTIONS) — safe to run through
                                                    // a Livewire call. Anything else (built-in 'view', or a custom
                                                    // action this component doesn't know about) isn't wired up to
                                                    // runAction() and falls back to a plain link, same as it would
                                                    // reach NexusController::action() directly via a real page nav.
                                                    $isMutatingAction = in_array($rowAction->name, ['delete', 'restore', 'deletePermanent', 'duplicate'], true);
                                                @endphp
                                                @if($rowAction->name === 'edit' && ($module->config->slideOver ?? false))
                                                    <button type="button" title="{{ $rowAction->label }}"
                                                        class="btn btn-soft-primary btn-sm"
                                                        wire:click="openSlideOver('{{ $item->id }}')">
                                                        <i class="{{ nexus_icon($rowAction->icon, $module->name, 'default_icon') }} align-middle fs-18"></i>
                                                    </button>
                                                @elseif($isMutatingAction)
                                                    <button type="button" title="{{ $rowAction->label }}"
                                                        class="btn btn-soft-primary btn-sm"
                                                        wire:click="runAction('{{ $rowAction->name }}', '{{ $item->id }}')"
                                                        @if($rowAction->confirm ?? false) wire:confirm="@lang('nexus::translate.are_u_sure')" @endif>
                                                        <i class="{{ nexus_icon($rowAction->icon, $module->name, 'default_icon') }} align-middle fs-18"></i>
                                                    </button>
                                                @else
                                                    <a href="{{ route('nexus.module.action', [$module->name, $rowAction->name, 'id' => $item->id]) }}"
                                                        title="{{ $rowAction->label }}" class="btn btn-soft-primary btn-sm">
                                                        <i class="{{ nexus_icon($rowAction->icon, $module->name, 'default_icon') }} align-middle fs-18"></i>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="99" class="text-center py-4">@lang('nexus::translate.no_results_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tableData['data']->total() > 0)
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        {{ $tableData['data']->firstItem() }}&ndash;{{ $tableData['data']->lastItem() }} / {{ $tableData['data']->total() }}
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-light" wire:key="page-prev"
                            @disabled($tableData['data']->currentPage() <= 1)
                            wire:click="gotoPage({{ $tableData['data']->currentPage() - 1 }})">&laquo;</button>
                        @for ($p = 1; $p <= $tableData['data']->lastPage(); $p++)
                            <button type="button" wire:key="page-{{ $p }}"
                                class="btn btn-sm {{ $p === $tableData['data']->currentPage() ? 'btn-primary' : 'btn-light' }}"
                                wire:click="gotoPage({{ $p }})">{{ $p }}</button>
                        @endfor
                        <button type="button" class="btn btn-sm btn-light" wire:key="page-next"
                            @disabled($tableData['data']->currentPage() >= $tableData['data']->lastPage())
                            wire:click="gotoPage({{ $tableData['data']->currentPage() + 1 }})">&raquo;</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($module->config->slideOver ?? false)
        {{--
            No Bootstrap Offcanvas JS instance here — the panel's open/closed
            state is driven purely by $slideOverOpen (server-side), so its
            visibility is just a class on the rendered output like any other
            Livewire-reactive markup: no data-bs-toggle wiring to fight
            morphdom over on unrelated re-renders (sort, pagination, ...).
            Bootstrap's own .offcanvas/.show CSS still provides the slide
            transition; only the backdrop/close/Esc behavior is reimplemented
            here, in plain Livewire/Alpine.
        --}}
        @if($slideOverOpen)
            <div class="offcanvas-backdrop fade show" wire:click="closeSlideOver" wire:key="slideover-backdrop"></div>
        @endif
        <div class="offcanvas offcanvas-end {{ $slideOverOpen ? 'show' : '' }}"
             style="width: min(720px, 100vw);"
             x-on:keydown.escape.window="$wire.closeSlideOver()"
             wire:key="slideover-panel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">@lang('nexus::translate.edit')</h5>
                <button type="button" class="btn-close" wire:click="closeSlideOver" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                @if($slideOverOpen && $slideOverId)
                    @livewire('nexus-module-form', ['moduleName' => $module->name, 'id' => $slideOverId, 'embedded' => true], key('slideover-form-' . $slideOverId))
                @endif
            </div>
        </div>
    @endif
</div>
