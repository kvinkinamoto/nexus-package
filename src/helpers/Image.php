<?php

namespace Nodex\Nexus\helpers;

class Image
{
    public static function getStorageImage($url)
    {
        $src = '/nexus/images/no-image.jpg';
        if (empty($url)) {
            return $src;
        }
        $src = $url;
        return $src;
    }

    public static function productImage($url)
    {
        $src = '/nexus/images/no-image.jpg';
        if (empty($url)) {
            return $src;
        }
        $src = $url;
        $src = '/' . ltrim($src, '/');

        if (strpos($src, '/storage/') === false) {
            $src = '/storage' . $src;
        }

        return $src;
    }

    public static function productImages($urls)
    {
        $newUrls = [];
        foreach ($urls as $url) {
            $newUrls[] = self::productImage($url);
        }
        return $newUrls;
    }

    public static function usertImage($url)
    {
        $src = '/nexus/images/no-image.jpg';
        if (empty($url)) {
            return $src;
        }
        $src = $url;
        $src = '/' . ltrim($src, '/');

        if (strpos($src, '/storage/') === false) {
            $src = '/storage' . $src;
        }

        return $src;
    }

    public static function returnImage($url)
    {
        $src = $url;
        $src = '/' . ltrim($src, '/');

        if (strpos($src, '/storage/') === false) {
            $src = '/storage' . $src;
        }

        return $src;
    }
}
