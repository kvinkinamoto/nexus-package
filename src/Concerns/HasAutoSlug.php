<?php

namespace Nodex\Nexus\Concerns;

use Illuminate\Support\Str;

/**
 * Fills the slug column from the source column when a model is created without
 * one. A #[Field(type: 'slug', slugSource: ...)] column is otherwise populated
 * only by the admin form's "Generate" button (Livewire\ModuleForm::generateSlug()),
 * so saving without it would hit a NOT NULL violation on the slug column.
 *
 * Opt-in: add `use HasAutoSlug;` to a model and override slugColumn() /
 * slugSourceColumn() when they are not 'slug' / 'name'.
 */
trait HasAutoSlug
{
    protected static function bootHasAutoSlug(): void
    {
        static::creating(function (self $model) {
            $column = $model->slugColumn();
            $source = $model->slugSourceColumn();

            if (empty($model->{$column}) && ! empty($model->{$source})) {
                $model->{$column} = Str::slug($model->{$source});
            }
        });
    }

    protected function slugColumn(): string
    {
        return 'slug';
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }
}
