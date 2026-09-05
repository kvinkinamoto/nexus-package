<?php

namespace Nodex\Nexus\Services\MediaLibrary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Nodex\Nexus\Contracts\MediaLibrary\MediaLibraryInterface;
use Nodex\Nexus\Dto\MediaLibrary\MediaItemDto;
use Nodex\Nexus\Events\MediaAttached;
use Nodex\Nexus\Events\MediaAttaching;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Default MediaLibraryInterface implementation, backed by
 * spatie/laravel-medialibrary. See that interface's docblock for how to
 * swap this out entirely.
 */
class SpatieMediaLibraryService implements MediaLibraryInterface
{
    public function attach(Model $model, UploadedFile $file, string $collection = 'default'): MediaItemDto
    {
        $this->assertHasMedia($model);

        // Earliest point to reject/transform an upload — a listener rejects
        // by throwing, same as every other *ing hook in this package.
        event(new MediaAttaching($model, $file, $collection));
        nexus_action('nexus.media.attaching', $model, $file, $collection);

        // Dedup within the same collection: re-uploading a file whose
        // content already matches one already attached returns the
        // existing item instead of storing a second copy of it.
        $hash = hash_file('sha256', $file->getRealPath());
        $existing = $model->getMedia($collection)
            ->first(fn (Media $media) => $media->getCustomProperty('sha256') === $hash);

        if ($existing) {
            return $this->toDto($existing);
        }

        $media = $model->addMedia($file)
            ->withCustomProperties(['sha256' => $hash])
            ->toMediaCollection($collection);

        // getMedia() (called just above, for the dedup check) reads and
        // caches the model's 'media' relation the first time it's accessed
        // — toMediaCollection() writes the new row straight to the
        // database, bypassing that cache, so a caller reusing the same
        // $model instance for another attach()/list() call would otherwise
        // keep seeing the pre-upload (stale) collection for the rest of
        // this object's lifetime.
        $model->unsetRelation('media');

        $item = $this->toDto($media);
        event(new MediaAttached($model, $item, $collection));
        nexus_action('nexus.media.attached', $model, $item, $collection);

        return $item;
    }

    public function detach(Model $model, string $mediaId, string $collection = 'default'): void
    {
        $this->assertHasMedia($model);

        $model->getMedia($collection)->firstWhere('id', $mediaId)?->delete();
        $model->unsetRelation('media');
    }

    public function list(Model $model, string $collection = 'default'): Collection
    {
        $this->assertHasMedia($model);

        return $model->getMedia($collection)->map(fn (Media $media) => $this->toDto($media))->values();
    }

    public function reorder(Model $model, array $orderedMediaIds, string $collection = 'default'): void
    {
        $this->assertHasMedia($model);

        Media::setNewOrder($orderedMediaIds);
        $model->unsetRelation('media');
    }

    public function setFocalPoint(Model $model, string $mediaId, float $x, float $y, string $collection = 'default'): MediaItemDto
    {
        $this->assertHasMedia($model);

        $media = $model->getMedia($collection)->firstWhere('id', $mediaId);

        if (! $media) {
            throw new \InvalidArgumentException("Media [{$mediaId}] not found in collection [{$collection}].");
        }

        $media->setCustomProperty('focal_point', [
            'x' => max(0.0, min(1.0, $x)),
            'y' => max(0.0, min(1.0, $y)),
        ]);
        $media->save();

        // registerMediaConversions() reads the custom property we just
        // saved, so regenerating now (synchronously — every conversion here
        // is ->nonQueued()) makes the new crop visible immediately instead
        // of waiting for whatever next touches this media's derived files.
        app(FileManipulator::class)->createDerivedFiles($media);

        $model->unsetRelation('media');

        return $this->toDto($media->fresh());
    }

    private function assertHasMedia(Model $model): void
    {
        if (! $model instanceof HasMedia) {
            throw new \InvalidArgumentException(
                get_class($model).' must implement Spatie\MediaLibrary\HasMedia (add the '
                .'Nodex\Nexus\Concerns\HasNexusMedia trait) to use #[Field(type: \'gallery\')].'
            );
        }
    }

    private function toDto(Media $media): MediaItemDto
    {
        $variants = [];
        foreach (['sm', 'md', 'lg'] as $name) {
            if ($media->hasGeneratedConversion($name)) {
                $variants[$name] = $media->getUrl($name);
            }
        }

        return new MediaItemDto(
            id: (string) $media->id,
            name: $media->file_name,
            url: $media->getUrl(),
            thumbnailUrl: $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
            size: $media->size,
            mimeType: $media->mime_type,
            focalPoint: $media->getCustomProperty('focal_point'),
            variants: $variants,
        );
    }
}
