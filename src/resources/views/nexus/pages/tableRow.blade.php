@foreach ($tableData['data'] ?? [] as $item)
    <tr @if(isset($item->deleted_at) && $item->deleted_at != null) class="table-danger"
    style="text-decoration: line-through;" @endif>
        @if(isset($tableData['actionGroup']) || $module->config->table->actionGroup)
            <td>
                <div class="form-check ms-1">
                    <input type="checkbox" class="form-check-input form-check-input-single"
                        id="customCheck{{($tableData['data']->currentPage() - 1) * ($tableData['data']->perPage()) + $loop->index}}"
                        data-id="{{ $item->id }}">
                    <label class="form-check-label"
                        for="customCheck{{($tableData['data']->currentPage() - 1) * ($tableData['data']->perPage()) + $loop->index}}">&nbsp;</label>
                </div>
            </td>
        @endif
        @foreach ($tableData['columns'] as $key => $column)
            <td>
                @if(isset($column->action) /*&& isset($column->fieldName)*/)
                    <form method="POST"
                        onsubmit="return sendFormConfirm({{$column->actionConfirm ?? false}}, '{{$column->action}}')"
                        action="{{ route('nexus.module.action', [$module->name, $column->action, 'id' => $item->id]) }}">
                        @csrf

                        @if (View::exists(Str::lcfirst($module->name) . '::admin.actions.' . $column->action))
                            @includeIf(Str::lcfirst($module->name) . '::admin.actions.' . $column->action, ['action' => $column->action, 'fieldName' => $column->name])
                        @else
                            @includeIf('nexus::' . config('nexus.template') . '.templates.actions.' . $column->action, ['action' => $column->action, 'fieldName' => $column->name])
                        @endif
                        <input type="hidden" name="model_id" value="{{ $item->id }}">
                    </form>
                @elseif(isset($column->customField))
                    @if (View::exists(Str::lcfirst($module->name) . '::admin.custom_index_fields.' . $column->customField))
                        @includeIf(Str::lcfirst($module->name) . '::admin.custom_index_fields.' . $column->customField, ['fieldName' => $column->fieldName ?? $column->name])
                    @else
                        @includeIf('nexus::' . config('nexus.template') . '.templates.custom_index_fields.' . $column->customField, ['fieldName' => $column->fieldName ?? $column->name])
                    @endif
                @else
                    {{ $item->{$column->name} ?? '' }}
                    @if(isset($item->depth) && $item->depth > 0 && $column->name == 'id')
                        <span style="color: #ff6c2f;">|{{ str_repeat('_', (int) $item->depth) }} </span>
                    @endif
                @endif
            </td>
        @endforeach
        @if(isset($tableData['actions']) && !empty($tableData['actions']))
            <td>
                <div class="d-flex gap-2 justify-content-end">
                    @php
                        $isSoftDeletable = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($item));
                        $isDeleted = $isSoftDeletable ? $item->trashed() : false;
                    @endphp
                    @foreach ($tableData['actions'] as $action)
                        @if(!$action->isActive)
                            @continue
                        @endif
                        @if(
                            ($action->name === 'delete' && $isDeleted) ||
                            ($action->name === 'edit' && $isDeleted) ||
                            ($action->name === 'restore' && !$isDeleted) ||
                            ($action->name === 'deletePermanent' && !$isDeleted)
                        )
                            @continue
                        @endif
                        @php
                            $isDuplicateAction = $action->name === 'duplicate';
                            $hasRelations = !empty($module->config->duplicatableRelations) || ($module->config->isTree ?? false);
                            $isSlideOverEdit = $action->name === 'edit' && ($module->config->slideOver ?? false);
                        @endphp
                        @if($isSlideOverEdit)
                            <button type="button" title="{{ $action->label }}"
                                    class="btn btn-soft-primary btn-sm js-slideover-trigger"
                                    data-slideover-url="{{ route('nexus.module.action', [$module->name, $action->name, 'id' => $item->id]) }}">
                                <i class="{{ nexus_icon($action->icon, $module->name, 'default_icon') }} align-middle fs-18"></i>
                            </button>
                        @else
                            <form method="POST"
                                  @if($isDuplicateAction && $hasRelations)
                                      onclick="openDuplicateModal('{{ route('nexus.module.action', [$module->name, $action->name, 'id' => $item->id]) }}', '{{ $item->id }}')"
                                  @else
                                      onsubmit="return sendFormConfirm({{$action->confirm ?? false}}, '{{$action->label}}')"
                                      action="{{ route('nexus.module.action', [$module->name, $action->name, 'id' => $item->id]) }}"
                                  @endif
                                  >
                                @csrf
                                <input type="hidden" name="model_id" value="{{ $item->id }}">
                                <button type="{{ ($isDuplicateAction && $hasRelations) ? 'button' : 'submit' }}" title="{{ $action->label }}"
                                        class="btn btn-soft-primary btn-sm {{ ($action->confirm ?? false) ? 'confirm-action' : '' }}">
                                    {{-- <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}--}}
                                    <i class="{{ nexus_icon($action->icon, $module->name, 'default_icon') }} align-middle fs-18"></i>
                                </button>
                                @if(request()->getQueryString())
                                    <input type="hidden" name="redirect_query" value="{{ request()->getQueryString() }}">
                                @endif
                            </form>
                        @endif
                    @endforeach
                </div>
            </td>
        @endif
    </tr>
@endforeach
