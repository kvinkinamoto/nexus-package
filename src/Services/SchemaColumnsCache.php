<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Facades\Schema;

/**
 * Per-request memoization of Schema::getColumnListing(). Table structure
 * can't change mid-request, but the table-builder/filter code calls this
 * once per rendered table plus once per discovered relation, each one an
 * information_schema round-trip — this collapses repeats within a request
 * without caching across requests (schema can change on deploy, and there's
 * no invalidation hook wired up for that yet).
 */
class SchemaColumnsCache
{
    private static array $columns = [];

    public static function get(string $table): array
    {
        return self::$columns[$table] ??= Schema::getColumnListing($table);
    }
}
