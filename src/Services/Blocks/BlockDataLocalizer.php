<?php

namespace Nodex\Nexus\Services\Blocks;

/**
 * Resolves a PageBlock::$data array for public rendering: any value stored
 * as a locale-keyed map (BlockFieldDefinition::$translatable, e.g.
 * ['uk' => '...', 'en' => '...']) is collapsed to a single string for the
 * current request locale, so every block_types/{type}.blade.php partial
 * keeps reading $data->{field} as a plain scalar and needs no changes of
 * its own. A non-translatable field's value is never an array (image/url/
 * string cells always store a scalar), so this only ever touches
 * translatable ones — no BlockTypeRegistry lookup needed here.
 */
class BlockDataLocalizer
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function resolve(array $data, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');

        foreach ($data as $key => $value) {
            if (! is_array($value) || array_is_list($value)) {
                continue;
            }

            $data[$key] = $value[$locale]
                ?? $value[$fallbackLocale]
                ?? (reset($value) ?: null);
        }

        return $data;
    }
}
