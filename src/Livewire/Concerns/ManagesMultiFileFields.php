<?php

namespace Nodex\Nexus\Livewire\Concerns;

/**
 * Backs #[Field(type: 'images'|'videos')] — a plain JSON-array-of-paths
 * column (no #[Relation], unlike the legacy Vue gallery which modeled each
 * item as its own related row — see field_types/images.blade.php's/videos.blade.php's
 * docblock). Each item is picked one at a time through the same elFinder
 * popup image.blade.php/video.blade.php already use; resources/js/app.js's
 * delegated `input` listener on `[data-multi-field]` calls addMultiFileValue()
 * directly instead of relying on wire:model (there's no single stable DOM
 * element to bind an array to across add/remove).
 */
trait ManagesMultiFileFields
{
    public function addMultiFileValue(string $fieldName, string $path): void
    {
        $current = (array) ($this->data[$fieldName] ?? []);
        $current[] = ltrim($path, '/');
        $this->data[$fieldName] = array_values($current);
    }

    public function removeMultiFileValue(string $fieldName, int $index): void
    {
        $current = (array) ($this->data[$fieldName] ?? []);
        unset($current[$index]);
        $this->data[$fieldName] = array_values($current);
    }
}
