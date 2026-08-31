<?php

namespace Nodex\Nexus\Dto;

use Illuminate\Database\Eloquent\Model;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;

/**
 * Carries everything a class/callback-registered field type needs to render
 * itself, mirroring the variables the built-in field_types/*.blade.php
 * partials already receive implicitly via the surrounding Blade scope
 * (see templates/sections/base.blade.php) — a class or callback has no such
 * scope to inherit from, so it's passed explicitly here instead.
 */
final class FieldRenderContext
{
    public function __construct(
        public readonly FieldConfigDto $field,
        public readonly ?Model $model,
        public readonly object $module,
        public readonly ?string $action,
        public readonly ?string $tabLang,
        public readonly array $formData,
        public readonly array $modelSchema,
    ) {}
}
