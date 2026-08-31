{{--
    Livewire Етап 6 — near-verbatim port of User's own
    field_types/wishlist_table.blade.php: a read-only summary, no form
    binding at all, so it's rendered here exactly like the legacy version
    once given a live $model — the only difference is fetching that $model
    directly (dispatch.blade.php doesn't carry one through), since a fresh
    create has no id yet and this is simply empty until the first save.
--}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
@endphp
@if($model)
    @php
        $items = $model->wishlistItems()->with('wishlistable')->get();
        $groups = $items->groupBy('wishlistable_type');
    @endphp
    <div class="mb-3">
        @if($items->isEmpty())
            <div class="text-muted">@lang('user::translate.no_wishlist_items')</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>@lang('user::translate.wishlist_item')</th>
                            <th style="width: 170px;">@lang('user::translate.wishlist_added_at')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Nexus\Modules\Wishlist\Enums\WishlistableTypeEnum::cases() as $type)
                            @php
                                $group = ($groups[$type->modelClass()] ?? collect())->sortByDesc('created_at');
                            @endphp

                            @if($group->isNotEmpty())
                                <tr class="table-light">
                                    <td colspan="2">
                                        <span class="badge {{ $type->badgeClass() }}">{{ $type->label() }}</span>
                                        <span class="text-muted ms-1">({{ $group->count() }})</span>
                                    </td>
                                </tr>

                                @foreach($group as $item)
                                    @php
                                        $wishlistable = $item->wishlistable;
                                        $adminModule = $type->adminModule();
                                        $title = match (true) {
                                            $wishlistable instanceof \App\Nexus\Modules\ShopProduct\Models\ShopProduct => $wishlistable->name,
                                            default => null,
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($wishlistable)
                                                <a href="{{ route('nexus.module.action', [$adminModule, 'edit', 'id' => $wishlistable->id]) }}"
                                                   target="_blank"
                                                   class="text-body fw-medium">
                                                    {{ $title ?? '' }}
                                                    <span class="text-muted">(ID: {{ $wishlistable->id }})</span>
                                                </a>
                                            @else
                                                <span class="text-muted">
                                                    @lang('wishlist::translate.deleted_item') (ID: {{ $item->wishlistable_id }})
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
