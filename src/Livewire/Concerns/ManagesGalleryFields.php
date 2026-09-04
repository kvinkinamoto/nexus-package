<?php

namespace Nodex\Nexus\Livewire\Concerns;

use Illuminate\Support\Collection;
use Nodex\Nexus\Contracts\MediaLibrary\MediaLibraryInterface;

/**
 * Backs #[Field(type: 'gallery')] — unlike #[Field(type: 'images')]'s plain
 * JSON-array-of-paths column (ManagesMultiFileFields), this is a real media
 * library collection (dedup, thumbnails, reordering) via
 * MediaLibraryInterface — see that interface's docblock for how the storage
 * backend is swappable. Files attach directly to the persisted model the
 * moment they're picked, independent of save()/$this->data, the same way
 * most modern admin panels handle "attach a file" — so, unlike every other
 * field type here, a gallery field only works once the record already has
 * an id (see gallery.blade.php's "save the record first" branch for a new
 * record).
 */
trait ManagesGalleryFields
{
    /** @var array<string, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $galleryUpload = [];

    /**
     * Livewire's updated{Property}($value, $key) convention for a
     * wire:model="galleryUpload.{fieldName}" input — auto-uploads the
     * instant a file is picked, rather than needing a separate button.
     */
    public function updatedGalleryUpload($value, $key): void
    {
        $this->uploadGalleryFile($key);
    }

    public function uploadGalleryFile(string $fieldName): void
    {
        $file = $this->galleryUpload[$fieldName] ?? null;

        if (! $file || ! $this->id) {
            return;
        }

        $model = $this->resolveModuleConfig()->model::find($this->id);

        if (! $model) {
            return;
        }

        // TemporaryUploadedFile extends Illuminate\Http\UploadedFile —
        // attach() takes it as-is, no separate "move out of Livewire's temp
        // storage first" step needed.
        app(MediaLibraryInterface::class)->attach($model, $file, $fieldName);

        unset($this->galleryUpload[$fieldName]);
    }

    public function deleteGalleryItem(string $fieldName, string $mediaId): void
    {
        if (! $this->id) {
            return;
        }

        $model = $this->resolveModuleConfig()->model::find($this->id);

        if (! $model) {
            return;
        }

        app(MediaLibraryInterface::class)->detach($model, $mediaId, $fieldName);
    }

    public function reorderGalleryItem(string $fieldName, string $mediaId, string $direction): void
    {
        if (! $this->id) {
            return;
        }

        $model = $this->resolveModuleConfig()->model::find($this->id);

        if (! $model) {
            return;
        }

        $service = app(MediaLibraryInterface::class);
        $ids = $service->list($model, $fieldName)->pluck('id')->all();
        $position = array_search($mediaId, $ids, true);

        if ($position === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $position - 1 : $position + 1;

        if ($swapWith < 0 || $swapWith >= count($ids)) {
            return;
        }

        [$ids[$position], $ids[$swapWith]] = [$ids[$swapWith], $ids[$position]];
        $service->reorder($model, $ids, $fieldName);
    }

    /**
     * @return Collection<int, \Nodex\Nexus\Dto\MediaLibrary\MediaItemDto>
     */
    public function galleryItems(string $fieldName): Collection
    {
        if (! $this->id) {
            return collect();
        }

        $model = $this->resolveModuleConfig()->model::find($this->id);

        if (! $model) {
            return collect();
        }

        return app(MediaLibraryInterface::class)->list($model, $fieldName);
    }
}
