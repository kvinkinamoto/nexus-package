{{--
    <x-nexus::image :item="$item" sizes="..." alt="..." class="..." />

    Public-facing responsive <img>, built from a Dto\MediaLibrary\MediaItemDto
    (the same DTO #[Field(type: 'gallery')]'s admin partial already renders —
    see MediaLibraryInterface::list()). $item->variants is a name => url map
    of whichever named conversions Concerns\HasNexusMedia actually generated
    (see that trait's RESPONSIVE_WIDTHS — 'sm'=>400, 'md'=>800, 'lg'=>1200 —
    duplicated here since a Blade view can't reach into a trait's private
    const; keep both in sync if the breakpoints ever change).

    Expects: $item (MediaItemDto). Optional: $sizes (the <img sizes=""> value,
    default "100vw"), $alt (default $item->name), $class.
--}}
@php
    $__nexusVariantWidths = ['sm' => 400, 'md' => 800, 'lg' => 1200];
    $__nexusSrcset = collect($item->variants)
        ->filter(fn ($url, $name) => isset($__nexusVariantWidths[$name]))
        ->map(fn ($url, $name) => "{$url} {$__nexusVariantWidths[$name]}w")
        ->values()
        ->implode(', ');
@endphp
<img
    src="{{ $item->url }}"
    @if($__nexusSrcset !== '')
        srcset="{{ $__nexusSrcset }}"
        sizes="{{ $sizes ?? '100vw' }}"
    @endif
    alt="{{ $alt ?? $item->name }}"
    @if(isset($class))
        class="{{ $class }}"
    @endif
    loading="lazy"
>
