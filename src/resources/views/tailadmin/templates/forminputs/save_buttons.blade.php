<div class="flex flex-wrap gap-2">
    <button type="submit" id="save_btn" name="save"
            value="{{\Nodex\Nexus\Enums\AdminButtonTypeEnum::SAVE->value}}"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
        <i class="{{ nexus_icon('save') }}"></i>
        @lang('nexus::translate.save')
    </button>
    <button type="submit" id="save_and_close_btn" name="save"
        value="{{\Nodex\Nexus\Enums\AdminButtonTypeEnum::SAVE_AND_CLOSE->value}}"
        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">@lang('nexus::translate.save and close')</button>
    <button type="submit" id="save_and_new" name="save"
        value="{{\Nodex\Nexus\Enums\AdminButtonTypeEnum::SAVE_AND_NEW->value}}"
        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">@lang('nexus::translate.save and new')</button>
    <a href="{{ route('nexus.module.action', ['module' => $module->name, 'action' => 'index']) }}" id="go_to_index"
        class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">@lang('nexus::translate.Close')</a>
</div>
