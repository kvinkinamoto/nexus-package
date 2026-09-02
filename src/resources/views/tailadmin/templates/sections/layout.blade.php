<?php
$sectionClass = $section->class ?? null;
?>
<div class="mb-4 rounded-2xl border border-gray-200 bg-white {{ $sectionClass }} dark:border-gray-800 dark:bg-white/[0.02]"
    @if($section->hasTranslations) x-data="{ activeLangTab{{ $section->name }}: '{{ ($formData['languages'] ?? [])[0] ?? '' }}' }" @endif>
    <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
        <h3 class="flex items-center gap-2 text-base font-semibold text-gray-800 dark:text-white/90">
            @if($section->icon ?? false)
                <i class="{{ nexus_icon($section->icon) }} text-brand-500"></i>
            @endif
            @if(str_contains($section->name, '::'))
                @lang($section->name)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $section->name)
            @endif
        </h3>
    </div>
    @if($section->hasTranslations)
        <div class="flex gap-1 border-b border-gray-100 px-5 pt-3 dark:border-white/5">
            @foreach($formData['languages'] ?? [] as $lang)
                <button type="button" @click="activeLangTab{{ $section->name }} = '{{ $lang }}'"
                    class="border-b-2 px-3 py-2 text-sm font-medium uppercase"
                    :class="activeLangTab{{ $section->name }} === '{{ $lang }}' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    {{ $lang }}
                </button>
            @endforeach
        </div>
        <div class="px-5 pt-4">
            @yield('sectionFieldsTranslates' . $section->name)
        </div>
    @endif

    <div class="p-5">
        @yield('sectionFieldsNoTranslates' . $section->name)
    </div>
</div>
