<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nodex\Nexus\Services\FieldTypeRegistry;

/**
 * A hand-maintained field-type list goes stale the moment a partial is added
 * or removed (see .ai/rules — the same "docs silently drift from code" risk
 * that motivated assertRelationCoverage()'s dev-time guard for validation).
 * This command cross-checks the curated descriptions below against the real
 * partials on disk and the registry's own aliases/registered types every
 * time it runs, so drift shows up as a warning instead of silently rotting.
 */
class FieldTypesDocsCommand extends Command
{
    protected $signature = 'nexus:docs:field-types {--markdown= : Write the reference as a Markdown file to this path instead of printing a table}';

    protected $description = 'List every #[Field(type: ...)] value the Livewire admin form actually renders, with what each one does';

    /**
     * @var array<string, string> built-in type => one-line description
     */
    protected array $builtIn = [
        'string' => 'Single-line text input.',
        'text' => 'Multi-line textarea. Add `editor: true` to swap it for a CKEditor instance instead.',
        'number' => 'Numeric input.',
        'email' => 'Email input.',
        'password' => 'Password input. Typically paired with `required: false` on the update form and `showInInfolist: false`.',
        'boolean' => 'Toggle switch.',
        'birthday' => "Date input. `type: 'date'` is an alias for this same partial.",
        'datetime' => 'Date+time input.',
        'enum' => 'Dropdown backed by a PHP backed enum — set via `#[Field(enum: SomeEnum::class)]`.',
        'select' => 'Dropdown whose options are populated at runtime by an `AdminFormBuilding` listener setting `$field->customData`.',
        'phone' => 'Phone number input.',
        'json' => 'Read-only, pretty-printed JSON viewer.',
        'image' => 'Single-file elFinder picker for an image.',
        'video' => 'Single-file elFinder picker for a video.',
        'images' => 'Multi-file elFinder gallery picker — the column stores an array of paths.',
        'videos' => 'Multi-file elFinder gallery picker for videos — the column stores an array of paths.',
        'relation' => "Generic relation picker driven by `#[Relation(type: 'belongsTo'|'belongsToMany'|'hasOne'|'hasMany', ...)]`. `ajax: true` switches from preload to search-as-you-type; `ajaxMode: 'load'` keeps it searchable while still preloading.",
        'relationManager' => 'Read-only list of related records with links into the related module. Never posts data — excluded from validation coverage.',
        'repeater' => 'Child-row repeater table, driven by `#[RepeaterField]` columns on a hasMany relation. Matched by the presence of repeater columns, not by this type string.',
        'causer' => 'Read-only "performed by user" link (activity-log style fields).',
        'view' => "Injects an arbitrary Blade view: `#[Field(type: 'view', view: 'your::view')]`. The view receives `model`, `field`, `module`.",
        'gender' => 'Hardcoded male/female dropdown wired to the `User` model — not usable on an arbitrary module.',
        'widgetConfigFields' => 'Dashboard-widget configuration sub-form — framework-internal, not for module data.',
        'wishlist_table' => 'Hardcoded to this app\'s wishlist model — not usable on an arbitrary module.',
        'cart_table' => 'Hardcoded to this app\'s cart model — not usable on an arbitrary module.',
        'wishlistable_type_field' => 'Hardcoded to this app\'s wishlist polymorphic type — not usable on an arbitrary module.',
        'addresses_table' => 'Hardcoded to this app\'s address model — not usable on an arbitrary module.',
        'attribute_options_field' => 'Hardcoded to this app\'s product-attribute model — not usable on an arbitrary module.',
        'custom_history' => 'Hardcoded activity-history viewer for this app\'s models — not usable on an arbitrary module.',
        'custom_delivery_type' => 'Hardcoded to this app\'s delivery model — not usable on an arbitrary module.',
        'custom_delivery_method' => 'Hardcoded to this app\'s delivery model — not usable on an arbitrary module.',
        'custom_payment_method' => 'Hardcoded to this app\'s payment model — not usable on an arbitrary module.',
    ];

    /**
     * Partial files that exist for internal reasons and aren't themselves a
     * #[Field(type: ...)] value — excluded from the disk/curated-list diff.
     */
    protected array $notATypeItself = ['_label', '_native_select', 'dispatch', 'unsupported'];

    /**
     * Documented types matched by a structural check in dispatch.blade.php
     * rather than a livewire/field_types/{type}.blade.php partial on disk —
     * 'view' is handled inline via $field->userType, so it would otherwise
     * false-positive as "documented but missing".
     */
    protected array $structuralOnly = ['view'];

    public function handle(FieldTypeRegistry $registry): int
    {
        $partialsDir = __DIR__.'/../resources/views/tailadmin/livewire/field_types';
        $onDisk = collect(File::files($partialsDir))
            ->map(fn ($file) => str_replace('.blade.php', '', $file->getFilename()))
            ->reject(fn ($type) => in_array($type, $this->notATypeItself, true))
            ->values();

        $undocumented = $onDisk->diff(array_keys($this->builtIn));
        $stale = collect(array_keys($this->builtIn))->diff($onDisk)->diff($this->structuralOnly);

        foreach ($undocumented as $type) {
            $this->warn("No description for on-disk field type [{$type}] — add one to FieldTypesDocsCommand::\$builtIn.");
        }

        foreach ($stale as $type) {
            $this->warn("Documented field type [{$type}] has no partial on disk anymore — remove it from FieldTypesDocsCommand::\$builtIn.");
        }

        $aliases = $registry->getAliases();
        $registered = $registry->getRegisteredTypes();

        if ($markdownPath = $this->option('markdown')) {
            File::ensureDirectoryExists(dirname($markdownPath));
            File::put($markdownPath, $this->toMarkdown($aliases, $registered));
            $this->info("Field-type reference written to {$markdownPath}");

            return self::SUCCESS;
        }

        $rows = collect($this->builtIn)
            ->map(fn ($description, $type) => [$type, 'built-in', $description])
            ->values()->all();

        foreach ($aliases as $alias => $realType) {
            $rows[] = [$alias, "alias of '{$realType}'", $this->builtIn[$realType] ?? ''];
        }

        foreach ($registered as $type) {
            $rows[] = [$type, 'registered (module/plugin)', ''];
        }

        $this->table(['Type', 'Source', 'Description'], $rows);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $aliases
     * @param  string[]  $registered
     */
    protected function toMarkdown(array $aliases, array $registered): string
    {
        $lines = ['# Nexus field-type reference', '', '| Type | Source | Description |', '| --- | --- | --- |'];

        foreach ($this->builtIn as $type => $description) {
            $lines[] = "| `{$type}` | built-in | {$description} |";
        }

        foreach ($aliases as $alias => $realType) {
            $lines[] = "| `{$alias}` | alias of `{$realType}` | {$this->builtIn[$realType]} |";
        }

        foreach ($registered as $type) {
            $lines[] = "| `{$type}` | registered (module/plugin) | — |";
        }

        return implode("\n", $lines)."\n";
    }
}
