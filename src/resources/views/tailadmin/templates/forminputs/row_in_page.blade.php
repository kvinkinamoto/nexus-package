@php
    $row_in_page = request()->get('perPage', config('shop_config.category_in_page'));
@endphp
<div>
    <label for="perPage" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-400">
        @lang('nexus::translate.Row in page')
        <select class="h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" id="perPage" name="perPage">
            <option @if ($row_in_page == 10) selected="selected" @endif value="10">10</option>
            <option @if ($row_in_page == 25) selected="selected" @endif value="25">25</option>
            <option @if ($row_in_page == 50) selected="selected" @endif value="50">50</option>
            <option @if ($row_in_page == 100) selected="selected" @endif value="100">100</option>
        </select>
    </label>
    {!! $errors->first('perPage', '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>
