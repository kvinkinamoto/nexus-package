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
 * required; see Spatie's own docs for why). Registers one 'thumb'
 * conversion every gallery collection gets for free; override
 * registerMediaConversions() on the model itself (call
 * parent::registerMediaConversions() first) to add more.
 */
trait HasNexusMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued();
    }
}
