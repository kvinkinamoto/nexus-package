@php
    if (!isset($value)){
        $value = '';
    }
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($imageFieldId??'img') ? 'border-error-500' : 'border-gray-300 dark:border-gray-700');
@endphp
<div class="mb-4">
    <label for="feature_image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{$lableCaption??__('nexus::translate.image')}}</label>

    <a href="/{{old($imageFieldId??'img')??($value??'')}}" id="{{$imageFieldId??'img'}}_link" target="_blank" rel="noopener" class="mb-3 block">
        <img src="/{{old($imageFieldId??'img')??($value??'')}}" id="{{$imageFieldId??'img'}}_prev"
             class="block max-h-50 max-w-full rounded-lg object-contain">
    </a>
    <div class="flex items-center gap-2">
        <button data-inputid="{{$imageFieldId??'img'}}" type="button" class="popup_selector rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">@lang('nexus::translate.choose')</button>
        <input type="text" id="{{$imageFieldId??'img'}}" name="{{$imageFieldName??$imageFieldId??'img'}}"
               value="{{old($imageFieldId??'img')??($value??'')}}"
               class="{{ $inputClass }}">
    </div>
    {!! $errors->first($imageFieldId??'img', '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>
