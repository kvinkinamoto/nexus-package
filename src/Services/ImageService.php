<?php

namespace Nodex\Nexus\Services;


use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ImageService
{

    public function __construct()
    {
    }

    public static function getImageSize(string $fullImagePath)
    {
        if ($fullImagePath == null) {
            return null;
        }
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($fullImagePath);

            return ['width' => $image->width(), 'height' => $image->height()];
        } catch (\Exception $ex) {
            //dd($ex);
            return ['width' => 0, 'height' => 0];
        }
        return ['width' => 0, 'height' => 0];
    }

}
