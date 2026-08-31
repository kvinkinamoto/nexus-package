<?php

namespace Nodex\Nexus\Services;

/**
 * Thin wrapper around Eloquent's own Model::resolveRelationUsing() and
 * Model::addGlobalScope() — lets a module attach a relation/scope to
 * another module's model without that model's file ever referencing it.
 * See Attributes/AttachRelation.php and Attributes/AttachScope.php for
 * how these get discovered and called.
 */
class RelationRegistrar
{
    public function attachRelation(string $modelClass, string $name, string $declaringClass, string $method): void
    {
        $modelClass::resolveRelationUsing($name, function ($model) use ($declaringClass, $method) {
            return $declaringClass::$method($model);
        });
    }

    public function attachScope(string $modelClass, ?string $name, string $declaringClass, string $method): void
    {
        $scope = $declaringClass::$method();
        $modelClass::addGlobalScope($name ?? $method, $scope);
    }
}
