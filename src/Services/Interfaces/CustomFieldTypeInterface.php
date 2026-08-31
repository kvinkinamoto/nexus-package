<?php

namespace Nodex\Nexus\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

interface CustomFieldTypeInterface {
    // Available data to field for example select options or enum values
    public function getData();

    // Default value for field for example selected item in select or checkbox checked by default
    public function getDefaultValue();

    // Render field in form edit/create
    public function renderField($model, $field, $modelSchema, $lang): string|View;

    // Render field in list view
    public function renderFieldInList($model): string|View;

    public function getCustomData(Model $model): mixed;

    public function getModel(): ?Model;



}
