<?php

namespace Nodex\Nexus\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
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
                $model->{$column} = $model->uniqueSlug(Str::slug($model->{$source}), $column);
            }
        });
    }

    /**
     * Appends -2, -3, ... until the slug is free, so two records with the
     * same title don't violate the unique index.
     */
    protected function uniqueSlug(string $base, string $column): string
    {
        $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($this), true);
        $query = fn () => $softDeletes ? static::withTrashed() : static::query();

        $slug = $base;
        $i = 2;

        while ($query()->where($column, $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
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
