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
    <div class="mb-4">
        @if($items->isEmpty())
            <div class="text-sm text-gray-400">@lang('user::translate.no_wishlist_items')</div>
        @else
            <div class="custom-scrollbar overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 dark:border-white/5">
                        <tr>
                            <th class="px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.wishlist_item')</th>
                            <th class="w-45 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.wishlist_added_at')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach(\App\Nexus\Modules\Wishlist\Enums\WishlistableTypeEnum::cases() as $type)
                            @php
                                $group = ($groups[$type->modelClass()] ?? collect())->sortByDesc('created_at');
                            @endphp

                            @if($group->isNotEmpty())
                                <tr class="bg-gray-50 dark:bg-white/[0.02]">
                                    <td colspan="2" class="px-3 py-2">
                                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $type->label() }}</span>
                                        <span class="ml-1 text-gray-400">({{ $group->count() }})</span>
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
                                        <td class="px-3 py-2">
                                            @if($wishlistable)
                                                <a href="{{ route('nexus.module.action', [$adminModule, 'edit', 'id' => $wishlistable->id]) }}"
                                                   target="_blank"
                                                   class="font-medium text-gray-700 hover:text-brand-500 dark:text-gray-300">
                                                    {{ $title ?? '' }}
                                                    <span class="text-gray-400">(ID: {{ $wishlistable->id }})</span>
                                                </a>
                                            @else
                                                <span class="text-gray-400">
                                                    @lang('wishlist::translate.deleted_item') (ID: {{ $item->wishlistable_id }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $item->created_at?->format('Y-m-d H:i') }}</td>
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
