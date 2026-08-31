<?php

namespace Nodex\Nexus\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Nodex\Nexus\Services\Interfaces\CustomFieldTypeInterface;
use Illuminate\Support\Facades\File;

class TemplateField implements CustomFieldTypeInterface
{
    protected string $moduleName;
    protected string $viewsPath;

    public function __construct(string $moduleName, ?string $viewsPath = null)
    {
        $this->moduleName = $moduleName;
        // Default to the module's resources/views folder
        $this->viewsPath = $viewsPath ?? app_path("Nexus/Modules/{$moduleName}/resources/views");
    }

    public function getData(): array
    {
        return [];
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function renderField($model, $field, $modelSchema, $lang): string|View
    {
        $customData = $this->getCustomData($model ?? new class extends Model {});
        
        $field->customData = $customData;

        return view('nexus::' . config('nexus.template') . '.templates.field_types.select', [
            'model' => $model,
            'field' => $field,
            'tab_lang' => $lang,
            'module' => (object)['name' => $this->moduleName],
        ])->render();
    }

    public function renderFieldInList($model): string|View
    {
        return '';
    }

    public function getCustomData(Model $model): mixed
    {
        $options = [];
        if (is_dir($this->viewsPath)) {
            $files = File::allFiles($this->viewsPath);
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php')) {
                    $relativePath = $file->getRelativePathname();
                    $relativePathWithoutExt = str_replace('.blade.php', '', $relativePath);
                    $dotNotation = str_replace(['/', '\\'], '.', $relativePathWithoutExt);
                    
                    $options[] = [
                        'value' => $dotNotation,
                        'name' => $dotNotation,
                    ];
                }
            }
        }
        return $options;
    }

    public function getModel(): ?Model
    {
        return null;
    }
}
