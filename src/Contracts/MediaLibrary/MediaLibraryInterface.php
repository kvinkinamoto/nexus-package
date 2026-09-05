<?php

namespace Nodex\Nexus\Contracts\MediaLibrary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Nodex\Nexus\Dto\MediaLibrary\MediaItemDto;

/**
 * Nexus's own boundary for "attach/list/remove files on a model, with
 * thumbnails and reordering" — #[Field(type: 'gallery')]'s Blade partial and
 * Livewire\ModuleForm's gallery methods talk to this interface only, never
 * to Spatie's classes directly, so swapping the storage backend is a normal
 * Laravel container rebind in your own service provider:
 *
 *   $this->app->bind(MediaLibraryInterface::class, YourOwnService::class);
 *
 * — no Nexus-specific override plumbing needed. See
 * config('nexus.media_library.enabled') to disable the 'gallery' field type
 * entirely instead; every other field type (including the elFinder-backed
 * image/images/video/videos) is unaffected regardless of this interface or
 * that flag.
 */
interface MediaLibraryInterface
{
    public function attach(Model $model, UploadedFile $file, string $collection = 'default'): MediaItemDto;

    public function detach(Model $model, string $mediaId, string $collection = 'default'): void;

    /**
     * @return Collection<int, MediaItemDto> in the current stored order
     */
    public function list(Model $model, string $collection = 'default'): Collection;

    /**
     * @param  string[]  $orderedMediaIds
     */
    public function reorder(Model $model, array $orderedMediaIds, string $collection = 'default'): void;

    /**
     * Sets the crop center used by a fixed-aspect conversion (e.g. 'thumb')
     * — see Concerns/HasNexusMedia's docblock on why only that one needs it,
     * not the proportional-resize responsive variants. $x/$y are fractional
     * (0-1) relative to the original image; out-of-range input is clamped,
     * not rejected. Implementations regenerate derived files synchronously
     * so the new crop is visible immediately, matching every conversion
     * here already being ->nonQueued().
     */
    public function setFocalPoint(Model $model, string $mediaId, float $x, float $y, string $collection = 'default'): MediaItemDto;
}
