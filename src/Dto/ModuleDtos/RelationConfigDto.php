<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

use Nodex\Nexus\Enums\RelationConfigParamsEnum;

class RelationConfigDto extends \stdClass
{
    public AjaxRelationConfigDto $ajaxConfig;

    public function __construct(
        public string $type,
        public string $relationName,
        public bool $isRequired = false,
        public string $showField = 'id',
        ?AjaxRelationConfigDto $ajaxConfig = null,
        public ?string $relatedModule = null,
    ) {
        $this->ajaxConfig = $ajaxConfig ?? new AjaxRelationConfigDto();
    }

    public function type(RelationConfigParamsEnum|string $type): self
    {
        $this->type = $type instanceof RelationConfigParamsEnum ? $type->value : $type;
        return $this;
    }

    public function hasOne(): self
    {
        $this->type = RelationConfigParamsEnum::HAS_ONE->value;
        return $this;
    }

    public function belongsTo(): self
    {
        $this->type = RelationConfigParamsEnum::BELONGS_TO->value;
        return $this;
    }

    public function hasMany(): self
    {
        $this->type = RelationConfigParamsEnum::HAS_MANY->value;
        return $this;
    }

    public function belongsToMany(): self
    {
        $this->type = RelationConfigParamsEnum::BELONGS_TO_MANY->value;
        return $this;
    }

    public function required(bool $isRequired = true): self
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    public function show(string $showField): self
    {
        $this->showField = $showField;
        return $this;
    }

    public function ajax(bool $isAjax = true, ?callable $callback = null): self
    {
        $this->ajaxConfig->enable($isAjax);
        if ($callback) {
            $callback($this->ajaxConfig);
        }
        return $this;
    }

    public static function fromArray(array|\stdClass $field)
    {
        $field = (object) $field;
        return new self(
            type: $field->type,
            relationName: $field->relationName,
            isRequired: $field->isRequired ?? false,
            showField: $field->showField ?? 'id',
            ajaxConfig: AjaxRelationConfigDto::fromArray($field->ajaxConfig ?? $field),
            relatedModule: $field->relatedModule ?? null,
        );
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRelationName(): string
    {
        return $this->relationName;
    }

    public function toArray(): array
    {
        return [
            'isRequired' => $this->isRequired,
            'type' => $this->type,
            'relationName' => $this->relationName,
            'showField' => $this->showField,
            'ajaxConfig' => $this->ajaxConfig->toArray(),
            'relatedModule' => $this->relatedModule,
        ];
    }
}
