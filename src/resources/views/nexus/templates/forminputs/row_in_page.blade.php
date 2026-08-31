<div class="dataTables_length">
    <?php
    $row_in_page = request()->get('perPage', config('shop_config.category_in_page'));
    ?>
    <label for="perPage" class="">@lang('nexus::translate.Row in page')
        <select class="form-control form-control-sm" id="perPage" name="perPage">
            <option @if ($row_in_page == 10) selected="selected" @endif value="10">10</option>
            <option @if ($row_in_page == 25) selected="selected" @endif value="25">25</option>
            <option @if ($row_in_page == 50) selected="selected" @endif value="50">50</option>
            <option @if ($row_in_page == 100) selected="selected" @endif value="100">100</option>
        </select>
    </label>
    {!! $errors->first('perPage', '<small class="error invalid-feedback">:message</small>') !!}
</div>
