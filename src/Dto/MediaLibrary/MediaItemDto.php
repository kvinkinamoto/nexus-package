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
    ) {}
}
