<?php

namespace Nodex\Nexus\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Implemented by a module's resolver class to say how a given model's
 * front-end URL is built. Wired up via #[Module(menuResolver: SomeResolver::class)]
 * (AttributeSchemaReader maps that into config->resolvers['menu']) and
 * looked up generically by MenuLinkService::resolveUrl() — no module needs
 * to know which other modules implement it.
 */
interface UrlResolverInterface
{
    public function resolve(Model $model): ?string;
}
