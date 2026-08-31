<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Marks a model property as a form field in the Nexus admin panel.
 * Can be placed on public properties or Eloquent relation methods.
 *
 * Example on property:
 * #[Field(type: 'string', section: 'main', label: 'Заголовок', translated: true)]
 * public string $title;
 *
 * Example on relation method:
 * #[Field(type: 'relation', section: 'settings')]
 * public function category(): BelongsTo { ... }
 *
 * View Slot example (inject arbitrary Blade template):
 * #[Field(type: 'view', section: 'settings', view: 'myModule::admin.components.price-preview')]
 * public string $price_preview; // virtual field — no real DB column needed
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Field
{
    public function __construct(
        /**
         * Field type. Built-in types:
         *   'string', 'text', 'number', 'email', 'password', 'boolean',
         *   'image', 'images', 'video', 'videos',
         *   'relation', 'enum', 'date', 'datetime', 'select',
         *   'editor' (alias for text+isEditor=true),
         *   'view' (inject custom Blade template — requires $view parameter)
         */
        public readonly string $type,

        /** Section key this field belongs to */
        public readonly string $section = 'main',

        /** Human-readable label. Auto-generated from property name if null. */
        public readonly ?string $label = null,

        /** Whether the field value should be empty/not required */
        public readonly bool $required = true,

        /** Whether this is a translatable (multilingual) field */
        public readonly bool $translated = false,

        /** Enable WYSIWYG editor (applicable when type = 'text') */
        public readonly bool $editor = false,

        /** Default value for the field */
        public readonly mixed $default = null,

        /** Enum class to use for select/radio options */
        public readonly ?string $enum = null,

        /** Action name for interactive fields (e.g. 'boolToggle') */
        public readonly ?string $action = null,

        /** Action field name in DB (if different from property name) */
        public readonly ?string $actionField = null,

        /** Whether the field is disabled. Use 'create' or 'edit' to limit to a context. */
        public readonly bool|string $disabled = false,

        /**
         * For type='view': Blade view namespace::path to render.
         * The view receives $model, $field, $module variables.
         * Example: 'shopProduct::admin.components.discount-preview'
         */
        public readonly ?string $view = null,

        /**
         * For type='relation': additional config overrides.
         * Example: ['path_field' => 'path']
         */
        public readonly array $relationConfig = [],

        /**
         * Display order within the section (lower = higher).
         * If not specified, order follows declaration order in the class.
         */
        public readonly int $order = 0,

        /**
         * Validation rules for both store and update actions.
         * Accepts a pipe-separated string or an array.
         * Example: 'required|string|max:255' or ['required', 'string', 'max:255']
         * For translatable fields, rules are automatically applied to each locale (e.g. field.*).
         */
        public readonly string|array $rules = [],

        /**
         * Validation rules for the store action only (overrides $rules for store).
         */
        public readonly string|array $storeRules = [],

        /**
         * Validation rules for the update action only (overrides $rules for update).
         */
        public readonly string|array $updateRules = [],

        /**
         * Conditions under which this field is shown. Normalized form per
         * condition: ['field' => 'otherFieldName', 'op' => 'eq|neq|in|notIn|truthy|falsy|gt|lt|contains', 'value' => mixed].
         * Empty (default) means always shown. See Services/FieldVisibilityEvaluator.php
         * for the single source of truth on how these are evaluated.
         * Example: showWhen: [['field' => 'type', 'op' => 'eq', 'value' => 'physical']]
         */
        public readonly array $showWhen = [],

        /** How multiple $showWhen conditions combine: 'and' (default) or 'or'. */
        public readonly string $showWhenLogic = 'and',

        /** Whether to clear this field's value client-side when it becomes hidden. */
        public readonly bool $clearWhenHidden = false,

        /**
         * Whether this field should be exposed in the auto-generated API resource.
         * Fields with apiExpose: true are included in NexusResource::toArray().
         * If false (default), the field is admin-only and not returned in API responses.
         *
         * Example:
         *   #[Field(type: 'string', section: 'main', translated: true, apiExpose: true)]
         *   private string $title;
         */
        public readonly bool $apiExpose = false,

        /**
         * Whether this field appears on the read-only Infolist (view) screen.
         * Default true — turn off for technical/sensitive fields that make
         * sense as form inputs but not as a displayed value (e.g. password).
         */
        public readonly bool $showInInfolist = true,
    ) {}
}
