<?php

namespace Brilliant\Brilara\Core\helpers;

class Filter
{
    public static function search($value)
    {
        return $value = is_array($value) ? implode(',', $value) : $value;
    }
}
