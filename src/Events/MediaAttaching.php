<?php

namespace Nodex\Nexus\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.media.attaching plugin filter,
 * fired right before it in SpatieMediaLibraryService::attach() — before the
 * sha256 dedup check, so it's the earliest point to reject or transform an
 * upload for any #[Field(type: 'image'|'video'|'gallery')] field. There is
 * no separate veto flag: a listener rejects an upload by throwing (a normal
 * exception propagates out of attach() uncaught, same as every other
 * *ing hook in this package).
 *
 *   class RejectOversizedUploads {
 *       public function handle(MediaAttaching $event): void {
 *           if ($event->file->getSize() > 10 * 1024 * 1024) {
 *               throw new \RuntimeException('File too large.');
 *           }
 *       }
 *   }
 */
class MediaAttaching
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public UploadedFile $file,
        public string $collection
    ) {
    }
}
