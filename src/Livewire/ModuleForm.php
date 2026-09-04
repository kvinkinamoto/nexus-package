<?php

namespace Nodex\Nexus\Livewire;

use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Livewire\Component;
use Livewire\WithFileUploads;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;
use Nodex\Nexus\Enums\RelationConfigParamsEnum;
use Nodex\Nexus\Events\AdminFormBuilding;
use Nodex\Nexus\Events\ModuleActionExecuted;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Livewire\Concerns\CallsLegacyActionMethods;
use Nodex\Nexus\Livewire\Concerns\ManagesGalleryFields;
use Nodex\Nexus\Livewire\Concerns\ManagesMultiFileFields;
use Nodex\Nexus\Livewire\Concerns\ManagesRelationPicker;
use Nodex\Nexus\Livewire\Concerns\ManagesRepeaterFields;
use Nodex\Nexus\Livewire\Concerns\ManagesWizardSteps;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\Actions\Admin\StoreActionMethod;
use Nodex\Nexus\Services\Actions\Admin\UpdateActionMethod;
use Nodex\Nexus\Services\FieldVisibilityEvaluator;
use Nodex\Nexus\Services\FormBuilder;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\RelationService;
use Nodex\Nexus\Services\Validation\NexusRuleCollector;

/**
 * Generic reactive replacement for pages/create.blade.php and
 * pages/edit.blade.php's full-page POST + redirect cycle. One component
 * serves every #[Module(livewire: true)] module — see ModuleTable's docblock
 * for the same reasoning. Reuses StoreActionMethod/UpdateActionMethod and
 * NexusRuleCollector exactly as the legacy controller does; Livewire replaces
 * only the transport, submission, and field-visibility mechanism.
 *
 * Scope for this pilot stage (Фаза 8.2, Livewire Етап 3-6): string/text/
 * number/boolean/enum/image/datetime/json fields (translatable or not — see
 * activeLocales()) plus #[RepeaterField] (native $relationRows array-state,
 * pilot on Cart), belongsTo/belongsToMany/hasMany ajax relation fields,
 * multi-step #[Module(wizard: true)] forms ($currentStep, one-final-save
 * contract — see save()), and a handful of small bespoke one-module types
 * ('wishlistable_type_field', 'causer' — each ported 1:1 from that module's
 * own legacy field_types partial, not a generic mechanism). Anything else
 * (video, multi-image galleries, other custom userType fields...) still
 * falls through to field_types/unsupported.blade.php and works only via the
 * legacy pages/create|edit.blade.php path.
 */
class ModuleForm extends Component
{
    use CallsLegacyActionMethods;
    use ManagesGalleryFields;
    use ManagesMultiFileFields;
    use ManagesRelationPicker;
    use ManagesRepeaterFields;
    use ManagesWizardSteps;
    use WithFileUploads;

    public string $moduleName;

    public ?string $id = null;

    public array $data = [];

    /**
     * True when this component is mounted inline inside ModuleTable's
     * slide-over panel rather than as the whole page (see
     * pages/editLivewire.blade.php vs livewire/module-table.blade.php's
     * offcanvas). Changes what save()/cancel() do on completion — see there.
     */
    public bool $embedded = false;

    /**
     * Index into array_keys($moduleConfig->tabs) for a wizard-mode
     * (#[Module(wizard:true)]) module — which step's fields are visible.
     * Purely presentational: nextStep()/prevStep()/goToStep() never touch
     * $data, and save() still validates and submits everything in one call
     * regardless of which step is showing (the "one final save" contract —
     * mirrors the legacy wizard_js.blade.php's client-side stepping, which
     * likewise never splits the POST).
     */
    public int $currentStep = 0;

    /** @var array<string, array<int, array<string, mixed>>> Keyed by relation/field name. */
    public array $relationRows = [];

    /** @var array<string, string|null> Display label for a single belongsTo relation field's current selection. */
    public array $relationLabels = [];

    /** @var array<string, string> Keyed by field name — the ajax search box's current text. */
    public array $relationSearchQuery = [];

    /** @var array<string, array<int, array{id: int|string, label: string}>> Keyed by field name. */
    public array $relationSearchResults = [];

    private ?Module $moduleCache = null;

    /**
     * Memoized within one Livewire round-trip only (mount()+render() together
     * on first load, or one update/action+render() cycle thereafter — a
     * private property never survives Livewire's between-requests hydration,
     * same as $moduleCache). Without this, every resolveModuleConfig() call
     * built a brand-new DTO straight from reflection, so an AdminFormBuilding
     * listener's mutation (e.g. Widget's PopulateWidgetSelectOptions setting
     * customData) never survived from wherever it was dispatched through to
     * render()'s own separately-resolved copy — the field looked populated
     * for an instant and then silently went back to empty options on the
     * very next render. Caching it here, with the event dispatched exactly
     * once per round-trip at cache-fill time, is what makes that mutation
     * actually visible to the view.
     */
    private ?DefaultModuleConfigurationDto $moduleConfigCache = null;

    public function mount(string $moduleName, ?string $id = null, bool $embedded = false): void
    {
        $this->moduleName = $moduleName;
        $this->id = $id;
        $this->embedded = $embedded;

        $action = $id ? 'edit' : 'create';
        abort_unless(ModuleManager::checkPermission($action, $this->resolveModule()), 403);

        $moduleConfig = $this->resolveModuleConfig();
        $model = $id ? $moduleConfig->model::query()->findOrFail($id) : null;

        foreach ($moduleConfig->form->fields as $field) {
            $relationConfig = $moduleConfig->relations->is_available[$field->name] ?? null;

            if ($relationConfig && ! empty($field->repeaterColumns)) {
                $this->relationRows[$field->name] = $model
                    ? $this->hydrateRepeaterRows($model, $field)
                    : [];

                continue;
            }

            if ($relationConfig && $relationConfig->type === RelationConfigParamsEnum::BELONGS_TO->value) {
                $related = $model?->{$field->name};
                $this->data[$field->name] = $related?->getKey() ?? ($model ? null : $field->default);
                $this->relationLabels[$field->name] = $related
                    ? app(RelationService::class)->formatLabel($related, $relationConfig->showField, $relationConfig->showFieldFallback)
                    : null;
                $this->seedRelationOptionsForLoadMode($field->name, $relationConfig);

                continue;
            }

            if ($relationConfig && $this->isMultipleRelation($relationConfig)) {
                $related = $model ? $model->{$field->name} : collect();
                $this->data[$field->name] = $related->map(fn ($item) => $item->getKey())->all();
                $this->relationLabels[$field->name] = $related
                    ->mapWithKeys(fn ($item) => [
                        $item->getKey() => app(RelationService::class)->formatLabel($item, $relationConfig->showField, $relationConfig->showFieldFallback),
                    ])
                    ->all();
                $this->seedRelationOptionsForLoadMode($field->name, $relationConfig);

                continue;
            }

            if ($relationConfig) {
                // hasOne/custom single-relation fields aren't part of this
                // pilot's scope — left unset so the view falls through to
                // field_types/unsupported.blade.php rather than silently
                // mis-rendering.
                continue;
            }

            if ($field->isTranslate) {
                $this->data[$field->name] = $model
                    ? $this->readTranslatedAttribute($model, $field)
                    : $this->defaultTranslatedValue($field);

                continue;
            }

            if ($field->type === 'enum') {
                $value = $model ? $this->readModelAttribute($model, $field->name) : $field->default;
                $this->data[$field->name] = $value instanceof \UnitEnum ? ($value->value ?? $value->name) : $value;

                continue;
            }

            if ($field->type === 'datetime') {
                // HTML5 datetime-local's wire format is "Y-m-d\TH:i" (no
                // seconds/timezone) — the model's own 'datetime' cast (a
                // Carbon instance) formats to it directly; the round trip
                // back through validate()'s 'date' rule and the model's cast
                // parses that same string on save without any conversion
                // needed on this end.
                $value = $model ? $this->readModelAttribute($model, $field->name) : null;
                $this->data[$field->name] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i') : null;

                continue;
            }

            if ($field->type === 'json') {
                // Read-only pretty-printed display (see field_types/json.blade.php)
                // — ActivityLog's `properties` is the only user of this type,
                // always #[Field(disabled: true)], so there's no write path
                // back through this string to worry about.
                $value = $model ? $this->readModelAttribute($model, $field->name) : null;
                $this->data[$field->name] = $value !== null
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null;

                continue;
            }

            if ($field->type === 'gender') {
                // Same scalar-unwrap as the 'enum' branch above (the model
                // casts this to a real Gender::class instance) — kept as its
                // own field type rather than folded into 'enum' because
                // Gender::label() resolves translations under a nested
                // 'enum_genders.{NAME}' key, not 'enum'.blade.php's flat
                // '{module}::translate.{value}' convention (see
                // field_types/gender.blade.php, which calls Gender::all()
                // to get correctly-labeled options instead).
                $value = $model ? $this->readModelAttribute($model, $field->name) : $field->default;
                $this->data[$field->name] = $value instanceof \UnitEnum ? ($value->value ?? $value->name) : $value;

                continue;
            }

            if ($field->type === 'password') {
                // Never pre-fill with the current hash — on edit this is a
                // "set a new password" field, empty means "leave unchanged"
                // (see User's AdminUpdateRequest::prepareForValidation(),
                // which strips an empty password from the request entirely
                // before it ever reaches UpdateActionMethod).
                $this->data[$field->name] = null;

                continue;
            }

            if ($field->type === 'birthday') {
                $value = $model ? $this->readModelAttribute($model, $field->name) : null;
                $this->data[$field->name] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : null;

                continue;
            }

            if ($field->type === 'attribute_options_field') {
                // Bespoke, not a generic mechanism: posts under a hardcoded
                // 'attribute_options' key (not $field->name — 'attributeOptions'),
                // read directly by ShopProduct's own Hooks\Update::syncAttributeOptions().
                // Keyed by ProductAttribute row id (not ShopAttribute id) — a
                // pivot-like row that only exists once `attributes` has
                // actually been saved at least once, same limitation the
                // legacy field_types/attribute_options_field.blade.php has
                // (it reads $model->productAttributes(), empty on a fresh
                // create or for an attribute just picked but not yet saved).
                $this->data['attribute_options'] = [];
                if ($model) {
                    foreach ($model->productAttributes()->with('options')->get() as $productAttribute) {
                        $this->data['attribute_options'][$productAttribute->id] = $productAttribute->options
                            ->pluck('id')
                            ->map(fn ($id) => (string) $id)
                            ->all();
                    }
                }

                continue;
            }

            if ($field->type === 'widgetConfigFields') {
                // Always an array (never null) so `wire:model="data.config.*"`
                // in field_types/widgetConfigFields.blade.php can write into
                // it directly on a fresh create — see that partial's docblock.
                $this->data[$field->name] = $model ? (array) $this->readModelAttribute($model, $field->name) : [];

                continue;
            }

            if ($field->type === 'image') {
                // 'image'/'horizontalImage'/'images'-style fields are usually
                // real morphOne/morphMany relations to the Images module's
                // own Image model, but deliberately carry no #[Relation]
                // attribute (see ShopCategory) — they aren't picked/searched
                // like a normal relation field, just a single elFinder-selected
                // path string read/written through
                // ProcessesImages::processImageFields() (see
                // field_types/image.blade.php and its .popup_selector button).
                // $model->{$field->name} triggers the real relation in that
                // case — but Image's own admin screen declares 'path' itself
                // (a plain string column, no relation at all) as type
                // 'image', so it needs the elFinder UI without any relation
                // to unwrap.
                $value = $model ? $model->{$field->name} : null;
                $this->data[$field->name] = $value instanceof Model ? $value->path : $value;

                continue;
            }

            $this->data[$field->name] = $model ? $this->readModelAttribute($model, $field->name) : $field->default;
        }
    }

    /**
     * Every #[Module] with the Language module enabled renders one row per
     * active language for a translatable field (see string.blade.php) —
     * FormBuilder::getLanguages() is the same source the legacy per-section
     * language-tab switcher uses, so the set of locales offered here matches.
     *
     * @return string[]
     */
    public function activeLocales(): array
    {
        return collect(FormBuilder::getLanguages())->map(fn ($locale) => (string) $locale)->values()->all();
    }

    /**
     * Spatie\Translatable's magic getter ($model->{$field}) resolves to the
     * *current locale's* string, not the full translations array — reading
     * that into $data would both mis-render (see string.blade.php's locale
     * rows) and mis-save (buildLegacyInput() would hand a scalar to a
     * 'required|array' rule / to HasTranslations' array-setter). This reads
     * the real per-locale map instead, filling in any locale the record
     * doesn't have a value for yet so every rendered row has a bound key.
     *
     * @return array<string, mixed>
     */
    private function readTranslatedAttribute(Model $model, FieldConfigDto $field): array
    {
        $translations = $model->getTranslations($field->name);

        foreach ($this->activeLocales() as $locale) {
            $translations[$locale] ??= $field->default ?? '';
        }

        return $translations;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultTranslatedValue(FieldConfigDto $field): array
    {
        return collect($this->activeLocales())
            ->mapWithKeys(fn ($locale) => [$locale => $field->default ?? ''])
            ->all();
    }

    /**
     * Some #[Field] declarations (e.g. Cart's `total`, type 'cart_total_field')
     * back a computed value the legacy pipeline only ever reads through a
     * dedicated field_types partial calling a real method (getTotal()), never
     * as a plain attribute — so under Eloquent's strict-attribute mode
     * $model->{$field->name} throws MissingAttributeException. Those field
     * types aren't in this pilot's rendered set anyway (they fall through to
     * field_types/unsupported.blade.php), so the value is unused regardless;
     * this just keeps mount() from crashing while reaching it. Also guards
     * against a #[Field]-annotated relation method that doesn't actually
     * return a Relation (Eloquent's magic getter throws a bare LogicException
     * for that, not MissingAttributeException) — a real bug in the model
     * declaration, but one this component shouldn't itself go down with; a
     * field like that was never going to render correctly anyway (falls
     * through to field_types/unsupported.blade.php the same as above).
     */
    private function readModelAttribute(Model $model, string $key): mixed
    {
        try {
            return $model->{$key};
        } catch (MissingAttributeException|\LogicException) {
            return null;
        }
    }

    public function clearImage(string $fieldName): void
    {
        $this->data[$fieldName] = null;
    }

    /**
     * Backs #[Field(type: 'slug', slugSource: '...')]'s "generate" button
     * (see livewire/field_types/slug.blade.php) — server-side so every
     * module gets Str::slug()'s exact behavior instead of a JS
     * reimplementation that could drift from it.
     */
    public function generateSlug(string $fieldName): void
    {
        $field = $this->resolveModuleConfig()->form->fields[$fieldName] ?? null;
        $source = $field->slugSource ?? null;

        if (! $source) {
            return;
        }

        $this->data[$fieldName] = \Illuminate\Support\Str::slug((string) ($this->data[$source] ?? ''));
    }

    /**
     * Widget-specific, not a generic mechanism: Livewire's magic
     * updated{Path}() hook for data.widget_key. Mirrors the legacy
     * widgetConfigFields.blade.php's inline keySelect 'change' listener,
     * which re-fetched both the widget_view options and the config-fields
     * body wholesale on every key change — clearing both here has the same
     * effect (field_types/widgetConfigFields.blade.php and the 'select'
     * partial for widget_view both read live state fresh on every render,
     * so there's nothing further to "refresh", just stale values to drop).
     * Gated to the one module that actually has this field, same as
     * PopulateWidgetSelectOptions gates on $event->moduleName itself.
     */
    public function updatedDataWidgetKey(): void
    {
        if ($this->moduleName !== 'widget') {
            return;
        }

        $this->data['widget_view'] = null;
        $this->data['config'] = [];
    }

    /**
     * @return FieldConfigDto[] Every field whose section's column belongs to
     *                          $tabName, in declaration order — the Livewire-native equivalent of
     *                          edit.blade.php's `@if($column->tab == $tab->name)` filter.
     */
    public function fieldsForTab(string $tabName): array
    {
        $moduleConfig = $this->resolveModuleConfig();

        return collect($moduleConfig->form->fields)
            ->filter(fn (FieldConfigDto $field) => $this->fieldTabName($moduleConfig, $field) === $tabName)
            ->values()
            ->all();
    }

    private function fieldTabName(DefaultModuleConfigurationDto $moduleConfig, FieldConfigDto $field): ?string
    {
        $section = $moduleConfig->sections[$field->section] ?? null;
        if (! $section) {
            return null;
        }

        return $moduleConfig->sectionColumns[$section->column]->tab ?? null;
    }

    /**
     * Mirrors StoreActionMethod/UpdateActionMethod's own rule resolution
     * (dedicated Request first, NexusRuleCollector otherwise) so the live
     * validation shown while typing matches exactly what the server will
     * enforce on save — one rule source, read twice.
     */
    public function rules(): array
    {
        $moduleConfig = $this->resolveModuleConfig();
        $methodName = $this->id ? 'update' : 'store';

        // Deliberately NOT GetModuleRequestAction::getRequestByMethodName()
        // here: resolving a FormRequest through the container triggers
        // Laravel's ValidatesWhenResolved wiring, which eagerly runs
        // ->rules() as real validation against whatever request() currently
        // holds — Livewire's JSON-wrapped update payload, not $this->data —
        // and throws before this method can even return. A plain `new`
        // reads the same rules() array without going through the container,
        // so nothing validates here; StoreActionMethod/UpdateActionMethod
        // still resolve the dedicated Request normally (with $this->data
        // merged into request() by save() beforehand) to do the real,
        // authoritative validation.
        $requestClass = $moduleConfig->methodRequests[$methodName] ?? null;
        $moduleRequest = $requestClass ? new $requestClass : null;

        // Same reasoning as makeFormRequest()'s $recordId merge: a rules()
        // body that reads $this->id (e.g. Rule::unique(...)->ignore($this->id))
        // would otherwise always see null on this route-less bare instance,
        // so on every edit the live preview would wrongly flag the record's
        // own already-persisted value as "already taken".
        if ($moduleRequest && $this->id !== null) {
            $moduleRequest->merge(['id' => $this->id]);
        }

        if ($moduleRequest && method_exists($moduleRequest, 'rules') && count($moduleRequest->rules()) > 0) {
            $rules = $moduleRequest->rules();
            app(NexusRuleCollector::class)->assertRelationCoverage($moduleConfig, $rules);
        } else {
            $rules = app(NexusRuleCollector::class)->collect($moduleConfig, $methodName, $this->data);
        }

        $mapped = [];
        foreach ($rules as $key => $rule) {
            $mappedKey = $this->remapRuleKey($key, $moduleConfig);
            if ($mappedKey !== null) {
                $mapped[$mappedKey] = $rule;
            }
        }

        return $mapped;
    }

    /**
     * Both NexusRuleCollector::collect() and a dedicated Request's rules()
     * key relation-backed rules as "relation.{name}" / "relation.{name}.*.{column}"
     * — the exact shape StoreRelationActionMethod expects on the wire, not a
     * Livewire property path. This translates each key onto the property
     * this component actually binds:
     *   - "relation" (the whole-payload "must be array" rule) -> dropped,
     *     meaningless as a single Livewire property.
     *   - "relation.{name}" where {name} is a repeater field -> the row
     *     collection's own array-level rule (e.g. nullable|array) -> dropped,
     *     same reason.
     *   - "relation.{name}[...]" where {name} is a single or multi relation
     *     (e.g. Cart's belongsTo `user`, or a belongsToMany/hasMany field —
     *     both mirrored into data.{name} by mount(), scalar or array
     *     respectively) -> "data.{name}[...]", "[...]" carried through as-is
     *     so a multi field's own "relation.{name}.*" per-item rule still
     *     validates each id in data.{name}.
     *   - "relation.{name}.*[...]" where {name} is a repeater field ->
     *     "relationRows.{name}.*[...]".
     *   - anything else (a plain field) -> "data.{key}", as before Етап 3.
     */
    private function remapRuleKey(string $key, DefaultModuleConfigurationDto $moduleConfig): ?string
    {
        if ($key === 'relation') {
            return null;
        }

        if (! preg_match('/^relation\.([^.]+)(.*)$/', $key, $matches)) {
            return "data.{$key}";
        }

        [, $relationName, $rest] = $matches;
        $field = $moduleConfig->form->fields[$relationName] ?? null;
        $isRepeater = $field && ! empty($field->repeaterColumns);

        if (! $isRepeater) {
            return "data.{$relationName}{$rest}";
        }

        return $rest === '' ? null : "relationRows.{$relationName}{$rest}";
    }

    /**
     * Single source of truth is still Services/FieldVisibilityEvaluator —
     * this just calls it against the component's live-typed values instead
     * of a submitted request array, so a showWhen-gated field appears and
     * disappears as the controlling field changes, no page reload and no
     * JS mirror (nexus-conditional-fields.js is not loaded for a
     * livewire-enabled module's form — see pages/createLivewire.blade.php
     * and pages/editLivewire.blade.php).
     */
    public function isFieldVisible(FieldConfigDto $field): bool
    {
        return app(FieldVisibilityEvaluator::class)->isVisible($field, $this->data);
    }

    /**
     * A single Livewire checkbox always synths to a real PHP bool (true/
     * false) on toggle, regardless of what type the bound value started as
     * — but a common convention across this codebase's *Request classes is
     * `Rule::in([0, 1])` / `'in:0,1'` on a boolean field, and Laravel's `in`
     * rule string-casts the value before comparing: `(string) false` is
     * `''`, not `'0'`, so an unchecked box would always fail validation
     * (checking it happens to pass by luck, since `(string) true` is `'1'`).
     * The legacy full-reload form never hits this because a real HTTP POST
     * only ever carries strings. Cast right before any validate() call
     * (save() and nextStep() both need it) rather than at read/mount time,
     * since the property genuinely is a bool while the checkbox is live.
     */
    private function normalizeBooleanFields(DefaultModuleConfigurationDto $moduleConfig): void
    {
        foreach ($moduleConfig->form->fields as $field) {
            if ($field->type === 'boolean' && array_key_exists($field->name, $this->data)) {
                $this->data[$field->name] = (int) $this->data[$field->name];
            }
        }
    }

    public function save(): void
    {
        $moduleConfig = $this->resolveModuleConfig();
        $this->normalizeBooleanFields($moduleConfig);
        $this->validate();

        $input = $this->buildLegacyInput($moduleConfig);
        $request = $this->makeFormRequest($input, $this->id);

        try {
            $this->withRealRedirector(function () use ($request, $moduleConfig) {
                if ($this->id) {
                    UpdateActionMethod::handle($request, $moduleConfig, $this->id);
                } else {
                    StoreActionMethod::handle($request, $moduleConfig, $this->id);
                }
            });
        } catch (\Throwable $exception) {
            // Same app.debug gate as NexusController::action() — rethrow in
            // local/dev so a real bug (or assertRelationCoverage()'s
            // dev-only guard) still surfaces normally instead of being
            // swallowed into this banner. In production, the alternative to
            // catching here was nothing: no try/catch existed at all, so a
            // save failure just crashed with no user-visible feedback.
            if (config('app.debug')) {
                throw $exception;
            }

            report($exception);
            $this->addError('form', __('nexus::translate.alert.action_error'));

            return;
        }

        event(new ModuleActionExecuted($moduleConfig->name, $this->id ? 'update' : 'store', id: $this->id));
        nexus_action('nexus.module.action_executed', $moduleConfig->name, $this->id ? 'update' : 'store', $this->id, null);

        if ($this->embedded) {
            // No page navigation to carry a flash message through — the
            // slide-over just closes and the table row updates in place
            // (see ModuleTable::onFormSaved()), which is confirmation enough.
            $this->dispatch('nexus-module-form-saved');

            return;
        }

        session()->flash('alert_message', __('nexus::translate.alert.'.($this->id ? 'update_success' : 'create_success')));
        session()->flash('alert_type', 'success');

        $this->redirect(route('nexus.module.action', ['module' => $moduleConfig->name, 'action' => 'index']));
    }

    /**
     * Embedded (slide-over) mode has no "index" page to navigate back to —
     * closing just means telling ModuleTable to hide the panel again,
     * mirroring nexus-slideover.js's hidden.bs.offcanvas reset but without
     * discarding any saved data (nothing was saved).
     */
    public function cancel(): void
    {
        if ($this->embedded) {
            $this->dispatch('nexus-module-form-saved');

            return;
        }

        $this->redirect(route('nexus.module.action', ['module' => $this->moduleName, 'action' => 'index']));
    }

    /**
     * Reassembles $this->data + $this->relationRows into the flat
     * relation[{name}][...] shape StoreRelationActionMethod/a dedicated
     * Request expect — the inverse of mount()'s split. A single relation
     * field's value moves from data.{name} to relation.{name} (it was only
     * kept under data.* so a plain wire:model could bind it); it isn't a
     * real model attribute so it's also removed from the top-level payload.
     */
    private function buildLegacyInput(DefaultModuleConfigurationDto $moduleConfig): array
    {
        $input = $this->data;
        $relation = [];

        foreach ($moduleConfig->relations->is_available as $name => $relationConfig) {
            $field = $moduleConfig->form->fields[$name] ?? null;

            if ($field && ! empty($field->repeaterColumns)) {
                // A new row's 'id' is null (see addRepeaterRow()) — the
                // legacy repeater's hidden id input is only rendered for a
                // row that already has one (_repeater_row.blade.php), so a
                // new row never carries an 'id' key at all on that path.
                // saveMultipleRelation() relies on that: it treats an empty
                // 'id' as "new" for its own routing decision, but still
                // passes the whole row array straight into Model::create(),
                // which would mass-assignment-reject an explicit null 'id'.
                $relation[$name] = array_values(array_map(
                    function (array $row) {
                        $row = Arr::except($row, ['_rowKey']);

                        return empty($row['id']) ? Arr::except($row, ['id']) : $row;
                    },
                    $this->relationRows[$name] ?? []
                ));
            } elseif (array_key_exists($name, $input)) {
                $relation[$name] = $input[$name];
                unset($input[$name]);
            }
        }

        $input['relation'] = $relation;

        return $input;
    }

    public function render()
    {
        return view('nexus::'.config('nexus.template').'.livewire.module-form', [
            'module' => $this->resolveModule(),
            'moduleConfig' => $this->resolveModuleConfig(),
            'action' => $this->id ? 'edit' : 'create',
        ]);
    }

    private function resolveModule(): Module
    {
        return $this->moduleCache ??= Module::findByName($this->moduleName) ?? throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Module::class);
    }

    private function resolveModuleConfig(): DefaultModuleConfigurationDto
    {
        if ($this->moduleConfigCache !== null) {
            return $this->moduleConfigCache;
        }

        $config = $this->resolveModule()->config;

        // Mirrors FormBuilder::build()'s own dispatch on the legacy path —
        // lets a module inject fields/tabs/sections (SEO's meta_title, etc.)
        // or mutate an existing field (Widget's customData-driven selects)
        // with zero coupling from this generic component. $model is looked
        // up fresh rather than reusing mount()'s copy since this can also be
        // the very first resolution, called from mount() itself.
        $currentModel = $this->id ? $config->model::query()->find($this->id) : null;

        event(new AdminFormBuilding(
            moduleName: $config->name,
            config: $config,
            model: $currentModel,
            liveData: $this->data,
        ));
        nexus_action('nexus.form.building', $config, $config->name, $currentModel, $this->data);

        return $this->moduleConfigCache = $config;
    }
}
