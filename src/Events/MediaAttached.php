<?php

namespace Nodex\Nexus\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Dto\MediaLibrary\MediaItemDto;

/**
 * Laravel-native counterpart to the nexus.media.attached plugin action,
 * fired right after it in SpatieMediaLibraryService::attach() — only for a
 * genuinely new upload, not the sha256-dedup early return (nothing new was
 * attached in that case). Side-effect only (thumbnail generation, malware
 * scan queueing, ...) — $item is a plain MediaItemDto, not mutable.
 *
 *   class QueueMalwareScan {
 *       public function handle(MediaAttached $event): void {
 *           ScanUploadJob::dispatch($event->model, $event->item->id);
 *       }
 *   }
 */
class MediaAttached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public MediaItemDto $item,
        public string $collection
    ) {
    }
}
