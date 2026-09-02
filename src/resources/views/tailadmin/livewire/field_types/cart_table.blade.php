{{-- Livewire Етап 6 — near-verbatim port of User's field_types/cart_table.blade.php (read-only, see wishlist_table.blade.php's docblock for the $model-fetch rationale). --}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
@endphp
@if($model)
    @php
        $cart = $model->cart()
            ->with(['products.product:id,name,price,price_discount', 'products.product.image'])
            ->first();

        $items = $cart
            ? $cart->products->map(function ($row) {
                $product = $row->product;
                $image = $product?->relationLoaded('image') ? $product->image : null;
                $effective = $product?->price_discount ?? $product?->price;

                return [
                    'product' => $product,
                    'product_id' => $row->product_id,
                    'quantity' => (int) $row->quantity,
                    'image' => ($image && $image->path) ? asset($image->path) : null,
                    'price' => $product?->price,
                    'price_discount' => $product?->price_discount,
                    'effective_price' => $effective,
                    'line_total' => $effective !== null
                        ? (float) $effective * (int) $row->quantity
                        : null,
                ];
            })
            : collect();

        $fmtPrice = static function ($value) {
            return $value === null ? '—' : number_format((float) $value, 2, '.', ' ');
        };
    @endphp

    <div id="user_cart_table" class="mb-4">
        @if($items->isEmpty())
            <div class="text-sm text-gray-400">@lang('user::translate.no_cart_products')</div>
        @else
            <div class="custom-scrollbar overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 dark:border-white/5">
                        <tr>
                            <th class="w-17.5 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('cart::translate.image')</th>
                            <th class="px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('cart::translate.product')</th>
                            <th class="w-27.5 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('cart::translate.quantity')</th>
                            <th class="w-50 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('cart::translate.price')</th>
                            <th class="w-32.5 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('cart::translate.total')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($items as $item)
                            @php
                                $product = $item['product'];
                                $hasDiscount = $item['price_discount'] !== null && $item['price'] !== null;
                            @endphp
                            <tr>
                                <td class="px-3 py-2 text-center">
                                    @if($item['image'])
                                        <img src="{{ $item['image'] }}" alt="" class="h-12 w-12 rounded-md border border-gray-200 object-cover dark:border-gray-800">
                                    @else
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-dashed border-gray-200 text-gray-400 dark:border-gray-700">
                                            <i class="bx bx-image text-xl"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($product)
                                        <a href="{{ route('nexus.module.action', ['shopProduct', 'edit', 'id' => $product->id]) }}"
                                           target="_blank"
                                           class="font-medium text-gray-700 hover:text-brand-500 dark:text-gray-300">
                                            {{ $product->name }}
                                            <span class="text-gray-400">(ID: {{ $product->id }})</span>
                                        </a>
                                    @else
                                        <span class="text-gray-400">
                                            @lang('cart::translate.deleted_product') (ID: {{ $item['product_id'] }})
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $item['quantity'] }}</td>
                                <td class="px-3 py-2">
                                    @if($hasDiscount)
                                        <span class="font-semibold text-error-500">{{ $fmtPrice($item['price_discount']) }}</span>
                                        <span class="ml-1 text-xs text-gray-400 line-through">{{ $fmtPrice($item['price']) }}</span>
                                    @else
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $fmtPrice($item['price']) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">{{ $fmtPrice($item['line_total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-100 bg-gray-50 dark:border-white/5 dark:bg-white/[0.02]">
                        <tr>
                            <th colspan="2" class="px-3 py-2 text-left">
                                <a href="{{ route('nexus.module.action', ['cart', 'edit', 'id' => $cart->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                    <i class="bx bx-link-external"></i>
                                    @lang('user::translate.open_cart')
                                </a>
                            </th>
                            <th colspan="2" class="px-3 py-2 text-right font-medium">@lang('cart::translate.cart_total')</th>
                            <th class="px-3 py-2 font-semibold">{{ $fmtPrice($cart->getTotal()) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
@endif
