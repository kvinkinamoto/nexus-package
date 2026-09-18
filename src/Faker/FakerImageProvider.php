<?php

namespace Nodex\Nexus\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FakerImageProvider extends Base
{
    public function picsum(string $dir = '', int $width = 500, int $height = 500, bool $saveOnDisk = false): string
    {
        $imageUrl = "https://picsum.photos/seed/" . Str::random(10) . "/{$width}/{$height}";

        if (!$saveOnDisk) {
            return $imageUrl;
        }

        $filename = Str::random(6) . '.jpg';
        $fullPath = "$dir/$filename";
        $imageContent = file_get_contents($imageUrl);

        Storage::disk('public')->put($fullPath, $imageContent);

        return '/storage/' . $fullPath;
    }
}
