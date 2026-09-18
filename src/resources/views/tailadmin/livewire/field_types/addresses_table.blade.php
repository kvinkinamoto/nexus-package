{{-- Livewire Етап 6 — near-verbatim port of User's field_types/addresses_table.blade.php (read-only, see wishlist_table.blade.php's docblock for the $model-fetch rationale). --}}
@php
    $model = $this->id ? $moduleConfig->model::find($this->id) : null;
@endphp
@if($model)
    @php
        $addresses = $model->addresses()->orderByDesc('is_main')->orderByDesc('id')->get();
    @endphp
    <div class="mb-4">
        @if($addresses->isEmpty())
            <div class="text-sm text-gray-400">@lang('user::translate.no_addresses')</div>
        @else
            <div class="custom-scrollbar overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 dark:border-white/5">
                        <tr>
                            <th class="px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.city')</th>
                            <th class="px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.street')</th>
                            <th class="w-25 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.house')</th>
                            <th class="w-25 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.apartment')</th>
                            <th class="w-25 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.is_main')</th>
                            <th class="w-42.5 px-3 py-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('user::translate.created_at')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($addresses as $address)
                            <tr>
                                <td class="px-3 py-2">{{ $address->city }}</td>
                                <td class="px-3 py-2">{{ $address->street }}</td>
                                <td class="px-3 py-2">{{ $address->house }}</td>
                                <td class="px-3 py-2">{{ $address->apartment ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if($address->is_main)
                                        <span class="rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-400">@lang('user::translate.is_main')</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $address->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
