<?php
 
namespace Nodex\Nexus\Dto\ModuleDtos;

use Nodex\Nexus\Enums\AjaxModeEnum;

class AjaxRelationConfigDto
{
    public bool $isAjax = false;
    public string $mode = 'search'; // AjaxModeEnum::SEARCH->value
    public ?string $route = null;
    public ?string $resource = null;

    public function __construct(
        bool $isAjax = false,
        string $mode = 'search',
        ?string $route = null,
        ?string $resource = null
    ) {
        $this->isAjax = $isAjax;
        $this->mode = $mode;
        $this->route = $route;
        $this->resource = $resource;
    }

    public function enable(bool $enable = true): self
    {
        $this->isAjax = $enable;
        return $this;
    }

    public function mode(AjaxModeEnum|string $mode): self
    {
        $this->mode = $mode instanceof AjaxModeEnum ? $mode->value : $mode;
        return $this;
    }

    public function route(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function resource(string $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public static function fromArray(array|object $data): self
    {
        $data = (object)$data;
        return new self(
            isAjax: $data->isAjax ?? false,
            mode: $data->mode ?? $data->ajaxMode ?? 'search',
            route: $data->route ?? null,
            resource: $data->resource ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'isAjax' => $this->isAjax,
            'mode' => $this->mode,
            'route' => $this->route,
            'resource' => $this->resource,
        ];
    }
}
