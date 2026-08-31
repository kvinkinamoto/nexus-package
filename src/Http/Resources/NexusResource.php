<?php

namespace Nodex\Nexus\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nodex\Nexus\Attributes\Field as FieldAttr;

/**
 * Auto-generated API Resource based on #[Field(apiExpose: true)] attributes.
 *
 * Usage (in routes/api.php of a module):
 *   return NexusResource::make($article);
 *   return NexusResource::collection(Article::paginate());
 *
 * To customize, create a Resource class in your module:
 *   app/Nexus/UserModules/Article/Http/Resources/ArticleResource.php
 * Nexus will automatically use your custom resource instead.
 */
class NexusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $modelClass = get_class($this->resource);
        $reflection = new \ReflectionClass($modelClass);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $fieldAttrs = $property->getAttributes(FieldAttr::class);
            if (empty($fieldAttrs)) {
                continue;
            }

            /** @var FieldAttr $fieldMeta */
            $fieldMeta = $fieldAttrs[0]->newInstance();

            if (!$fieldMeta->apiExpose) {
                continue;
            }

            $name = $property->getName();
            // getAttribute() reads through Eloquent's accessor regardless of
            // whether the model uses HasAttributeSchemaProperties — unlike
            // $this->resource->$name, which only resolves correctly because
            // that trait is applied (see AttributeSchemaReader's D22 guardrail).
            $data[$name] = $this->resource->getAttribute($name);
        }

        // Always include id and timestamps
        $data['id'] = $this->resource->getKey();
        if (isset($this->resource->created_at)) {
            $data['created_at'] = $this->resource->created_at;
        }
        if (isset($this->resource->updated_at)) {
            $data['updated_at'] = $this->resource->updated_at;
        }

        return $data;
    }

    /**
     * Resolve the correct resource class for a given model.
     * If the module has a custom Resource, use it; otherwise fall back to NexusResource.
     *
     * Example:
     *   NexusResource::resolveFor($article);
     */
    public static function resolveFor(mixed $model): static
    {
        $modelClass = get_class($model);
        $reflection = new \ReflectionClass($modelClass);
        $shortName = $reflection->getShortName(); // e.g. "Article"

        // Try to find a custom resource in the module's Http/Resources directory
        $namespace = $reflection->getNamespaceName(); // e.g. App\Nexus\UserModules\Article\Models
        $moduleNamespace = implode('\\', array_slice(explode('\\', $namespace), 0, -1)); // strip \Models
        $customResource = $moduleNamespace . '\\Http\\Resources\\' . $shortName . 'Resource';

        if (class_exists($customResource) && is_subclass_of($customResource, JsonResource::class)) {
            return new $customResource($model);
        }

        return new static($model);
    }
}
