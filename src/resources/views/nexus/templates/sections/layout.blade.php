<?php
//    dd($section);
$sectionClass = $section->class ?? null;
?>
<div class="card {{ $sectionClass }} mb-3">
    <div class="card-header">
        <h3 class="card-title d-flex align-items-center gap-1">
            {{-- @dump($section)--}}
            @if($section->icon ?? false)
                <i class="{{ nexus_icon($section->icon) }} text-primary fs-20"></i>
            @endif
            @if(str_contains($section->name, '::'))
                @lang($section->name)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $section->name)
            @endif
        </h3>
    </div>
    @if($section->hasTranslations)
        <ul class="nav nav-tabs" id="custom-tabs-{{ $sectionClass }}-tab" role="tablist">
            @foreach($formData['languages'] ?? [] as $lang)
                <li class="nav-item">
                    <a href="#tab-{{$lang}}-{{$section->name}}" data-bs-toggle="tab"
                        aria-expanded="@if($loop->first) true @else false @endif"
                        class="nav-link @if($loop->first) active @endif">
                        <span class="d-block d-sm-none">
                            <i class="{{ nexus_icon('default_icon') }}"></i>
                        </span>
                        <span class="d-none d-sm-block">
                            {{$lang}}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="card-header pb-0">
            <div class="tab-content col-12 pt-0" id="custom-tabs-{{ $sectionClass }}-tabContent">
                {{-- @dump('a1')--}}
                @yield('sectionFieldsTranslates' . $section->name)
            </div>
        </div>
    @endif

    <div class="card-body">
        {{-- @dump('a2')--}}
        @yield('sectionFieldsNoTranslates' . $section->name)
    </div>
</div>