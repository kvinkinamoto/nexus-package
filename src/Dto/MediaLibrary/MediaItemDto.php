<?php

namespace Nodex\Nexus\Dto\MediaLibrary;

/**
 * Presentation-ready shape for one attached file — what #[Field(type: 'gallery')]'s
 * Blade partial actually renders, so it never touches a Spatie Media model
 * (or whatever a swapped-in MediaLibraryInterface implementation uses)
 * directly.
 */
class MediaItemDto extends \stdClass
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public ?string $thumbnailUrl = null,
        public ?int $size = null,
        public ?string $mimeType = null,
        /** @var array{x: float, y: float}|null Fractional (0-1), null until an editor sets one — see SpatieMediaLibraryService::setFocalPoint(). */
        public ?array $focalPoint = null,
        /** @var array<string, string> Named responsive conversion URLs (e.g. 'sm'/'md'/'lg'), only those actually generated. */
        public array $variants = [],
    ) {}
}
