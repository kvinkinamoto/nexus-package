<div class="col-lg-2">
    <button type="submit" class="btn btn-primary w-100" id="save_btn" name="save"
            value="{{\Nodex\Nexus\Enums\AdminButtonTypeEnum::SAVE->value}}">
        <i class="{{ nexus_icon('save') }} font-size-16 align-middle me-2"></i>
        @lang('nexus::translate.save')
    </button>
</div>
<div class="col-lg-2">
    <button type="submit" class="btn btn-primary w-100" id="save_and_close_btn" name="save"
        value="{{\Nodex\Nexus\Enums\AdminButtonTypeEnum::SAVE_AND_CLOSE->value}}">@lang('nexus::translate.save and close')</button>
</div>
<div class="col-lg-2">
    <button type="submit" class="btn btn-primary w-100" id="save_and_new" name="save"
        value="{{\Nodex\Nexus\Enums\AdminButtonTypeEnum::SAVE_AND_NEW->value}}">@lang('nexus::translate.save and new')</button>
</div>
<div class="col-lg-2">
    <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'index']) }}"
        class="btn btn-outline-secondary w-100" id="go_to_index">@lang('nexus::translate.Close')</a>
</div>
