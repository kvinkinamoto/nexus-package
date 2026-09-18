<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.search.results plugin filter,
 * fired right before it in GlobalSearchService::search() — a module's own
 * Listeners/ folder can append its own result group (an external API, a
 * non-module data source) or re-rank/trim what every module already
 * contributed. $results is by reference, same shape search() returns.
 *
 *   class AppendDocsSearchResults {
 *       public function handle(GlobalSearchCompleted $event): void {
 *           $event->results[] = ['module' => 'docs', 'label' => 'Docs', 'items' => [...]];
 *       }
 *   }
 */
class GlobalSearchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array &$results,
        public string $term
    ) {
    }
}
