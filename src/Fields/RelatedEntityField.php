<?php

namespace Nodex\Nexus\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Nodex\Nexus\Services\Interfaces\CustomFieldTypeInterface;
use Nodex\Nexus\Services\RelatedEntityFieldService;

/**
 * Універсальне кастомне поле "Пов'язана сутність" (поліморфний зв'язок типу + id).
 *
 * Використання в ModuleConfiguration::form():
 *
 *   $form->field('sliderable_type', 'userType', 'section', 'Link')
 *       ->default(
 *           RelatedEntityField::make(
 *               morphType: 'sliderable_type',
 *               morphId:   'sliderable_id',
 *               moduleName: 'slider',
 *               allowedModules: ['shopCategory', 'blogPost'],
 *               urlLabel: null,           // null = без опції "URL"
 *           )
 *       )
 *       ->required(false);
 *
 * Для кількох полів у одному модулі — просто передайте різні morphType/morphId.
 * Кожен екземпляр генерує унікальні CSS-id на основі morphType.
 */
class RelatedEntityField implements CustomFieldTypeInterface
{
    protected Model $currentModel;
    protected string $morphType;
    protected string $morphId;
    protected string $moduleName;
    protected array $allowedModules = [];
    protected array $excludedModules = [];
    protected ?string $urlLabel = null;
    protected string $actionCall = 'getRelatedItems';
    protected ?string $labelField = null;
    protected string $customFieldName = 'url';
    protected ?RelatedEntityFieldService $service = null;

    /**
     * @param  string        $morphType      Назва поля типу в БД (напр. 'sliderable_type', 'menuable_type').
     * @param  string        $morphId        Назва поля id в БД (напр. 'sliderable_id', 'menuable_id').
     * @param  string        $moduleName     Назва модуля (для маршруту getRelatedItems).
     * @param  array         $allowedModules Дозволені модулі. Пусто = всі доступні.
     * @param  array         $excludedModules Додатково виключені модулі.
     * @param  string|null   $urlLabel       Мітка для опції "пряме посилання". null = без URL-опції.
     * @param  string        $actionCall     Назва екшну контролера для AJAX-підвантаження сутностей.
     * @param  string|null   $labelField     Яке поле моделі використовувати як лейбл (за замовчуванням визначається сервісом).
     */
    public function __construct(
        string $morphType,
        string $morphId,
        string $moduleName,
        array $allowedModules = [],
        array $excludedModules = [],
        ?string $urlLabel = 'URL',
        string $actionCall = 'getRelatedItems',
        string $customFieldName = 'url',
        ?string $labelField = null,
        ?RelatedEntityFieldService $service = null,
    ) {
        $this->morphType = $morphType;
        $this->morphId = $morphId;
        $this->moduleName = $moduleName;
        $this->allowedModules = $allowedModules;
        $this->excludedModules = $excludedModules;
        $this->urlLabel = $urlLabel;
        $this->actionCall = $actionCall;
        $this->labelField = $labelField;
        $this->service = $service ?? app(RelatedEntityFieldService::class);
    }

    /**
     * Фабричний метод для зручного створення без `new`.
     */
    public static function make(
        string $morphType,
        string $morphId,
        string $moduleName,
        array $allowedModules = [],
        array $excludedModules = [],
        ?string $urlLabel = 'URL',
        string $actionCall = 'getRelatedItems',
        string $customFieldName = 'url',
    ): static {
        return new static(
            morphType: $morphType,
            morphId: $morphId,
            moduleName: $moduleName,
            allowedModules: $allowedModules,
            excludedModules: $excludedModules,
            urlLabel: $urlLabel,
            actionCall: $actionCall,
            customFieldName: $customFieldName,
        );
    }

    public function getData(): array
    {
        return [];
    }

    public function getDefaultValue(): array
    {
        $type = $this->currentModel?->{$this->morphType} ?? null;

        if ($type && $type !== RelatedEntityFieldService::URL_TYPE) {
            return $this->service->getEntities($type);
        }

        return [];
    }

    public function renderField($model, $field, $modelSchema, $lang): string|View
    {
        $this->currentModel = $model ?? new class extends Model {};

        $selectedType = old($this->morphType, $model?->{$this->morphType});

        // Якщо є URL але немає типу — вважаємо що обраний URL-тип
        if (!empty($model?->url) && empty($selectedType) && $this->urlLabel !== null) {
            $selectedType = RelatedEntityFieldService::URL_TYPE;
        }

        // Унікальний префікс для CSS id на основі morphType (дозволяє кілька полів на одній формі)
        $fieldPrefix = str_replace('_type', '', $this->morphType);

        return view('nexus::'.config('nexus.template').'.templates.field_types.relatedEntity', [
            'model'       => $model,
            'field'       => $field,
            'modelSchema' => $modelSchema,
            'tab_lang'    => $lang,
            'customData'  => $this->getCustomData($this->currentModel),
            'defaultValue' => $this->getDefaultValue(),
            'moduleName'  => $this->moduleName,
            'selectedType' => $selectedType,
            'morphType'   => $this->morphType,
            'morphId'     => $this->morphId,
            'fieldPrefix' => $fieldPrefix,
            'urlLabel'    => $this->urlLabel,
            'route'       => route('nexus.module.action', [
                'module' => $this->moduleName,
                'action' => $this->actionCall,
            ]),
        ])->render();
    }

    public function renderFieldInList($model): string|View
    {
        return '';
    }

    public function getCustomData(Model $model): mixed
    {
        return $this->service->getLinkOptions(
            allowedModules: $this->allowedModules,
            excludedModules: $this->excludedModules,
            morphId: $this->morphId,
            customFieldName: $this->customFieldName,
            urlLabel: $this->urlLabel,
        );
    }

    public function getModel(): ?Model
    {
        return null;
    }
}
