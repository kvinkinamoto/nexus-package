<?php

namespace Nodex\Nexus\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Opts a model into #[Field(type: 'gallery')] — add this trait AND
 * `implements \Spatie\MediaLibrary\HasMedia` on the model itself (a PHP
 * trait can't satisfy an interface on the class's behalf, so both are
 * required; see Spatie's own docs for why). Registers the conversions every
 * gallery collection gets for free; override registerMediaConversions() on
 * the model itself (call parent::registerMediaConversions() first) to add
 * more.
 *
 * 'thumb' is focal-point-aware (see SpatieMediaLibraryService::setFocalPoint())
 * — a fixed-aspect crop necessarily cuts part of the image away, so it's the
 * one conversion here where the focal point matters. 'sm'/'md'/'lg' are
 * plain proportional resizes (Fit::Contain, no crop) for a public <img
 * srcset> — see resources/views/components/image.blade.php — so the focal
 * point is irrelevant to them; nothing in them is ever cut off.
 */
trait HasNexusMedia
{
    use InteractsWithMedia;

    /** @var array<string, int> Named breakpoint widths for the srcset conversions. */
    private const RESPONSIVE_WIDTHS = ['sm' => 400, 'md' => 800, 'lg' => 1200];

    public function registerMediaConversions(?Media $media = null): void
    {
        [$centerX, $centerY] = $this->resolveFocalCropCenter($media);

        $this->addMediaConversion('thumb')
            ->focalCrop(300, 300, $centerX, $centerY)
            ->nonQueued();

        foreach (self::RESPONSIVE_WIDTHS as $name => $width) {
            $this->addMediaConversion($name)
                ->fit(Fit::Contain, $width, $width)
                ->nonQueued();
        }
    }

    /**
     * Converts the fractional focal point stored on $media (see
     * SpatieMediaLibraryService::setFocalPoint(), default center when unset)
     * into the pixel coordinates focalCrop() expects, against the original
     * file's own dimensions. Deliberately plain getimagesize() rather than
     * Services\ImageService — that class hardcodes Intervention's Imagick
     * driver, which isn't installed in every environment this runs in (only
     * ext-gd is guaranteed here), and would silently fall back to a (0,0)
     * size on such a setup instead of throwing.
     *
     * @return array{0: int, 1: int}
     */
    private function resolveFocalCropCenter(?Media $media): array
    {
        if (! $media) {
            return [0, 0];
        }

        $focalPoint = $media->getCustomProperty('focal_point', ['x' => 0.5, 'y' => 0.5]);
        $size = @getimagesize($media->getPath());
        $width = $size[0] ?? 0;
        $height = $size[1] ?? 0;

        return [
            (int) round(($focalPoint['x'] ?? 0.5) * $width),
            (int) round(($focalPoint['y'] ?? 0.5) * $height),
        ];
    }
}
