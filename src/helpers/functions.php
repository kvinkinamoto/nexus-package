<?php

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Nodex\Nexus\Services\HookManager;
use Nodex\Nexus\Services\IconManager;

if (! function_exists('nexus_icon')) {

    function nexus_icon(?string $key, ?string $module = null, ?string $default = null): string
    {
        return app(IconManager::class)->getIcon($key, $module, $default);
    }
}

if (! function_exists('nexus_filter')) {

    /**
     * Run a named filter hook, transforming and returning $value.
     * See Nodex\Nexus\Services\HookManager for the full explanation.
     */
    function nexus_filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookManager::class)->filter($hook, $value, ...$args);
    }
}

if (! function_exists('nexus_action')) {

    /**
     * Run a named action hook (side effects, no return value).
     */
    function nexus_action(string $hook, mixed ...$args): void
    {
        app(HookManager::class)->action($hook, ...$args);
    }
}

if (! function_exists('nexus_trans_label')) {

    /**
     * Resolve the display text for a module's own column/field/section/
     * filter/setting label — every one of those blade partials builds a key
     * as {module}::translate.{lowercased label or field name} and hands it
     * straight to @lang(), which (Laravel's default) echoes the raw key
     * string back when the module's own lang file doesn't have that entry
     * yet (e.g. "shopCategory::translate.slug") instead of failing loudly.
     * Every module's DefaultModuleConfigurationDto ships the same built-in
     * columns/sections ("id", "information", "relations", "main"...), so
     * without a shared fallback every module's lang file would need the
     * exact same handful of entries duplicated into it. This tries the
     * module's own namespace first (so a module can still override/localize
     * any of these), then this package's own shared nexus::translate (see
     * its "id"/"information"/... entries), then finally falls back to the
     * raw human label itself — still readable, just not localized — rather
     * than the ugly namespaced key string.
     */
    function nexus_trans_label(string $moduleName, ?string $label, string $fallbackName): string
    {
        $label = $label ?: $fallbackName;

        if (str_contains($label, '::')) {
            return __($label);
        }

        $key = Str::lower($label);
        $moduleKey = Str::lcfirst($moduleName).'::translate.'.$key;

        if (Lang::has($moduleKey)) {
            return __($moduleKey);
        }

        $sharedKey = 'nexus::translate.'.$key;
        if (Lang::has($sharedKey)) {
            return __($sharedKey);
        }

        return $label;
    }
}

if (! function_exists('nexus_trans_action')) {

    /**
     * Resolve a #[TableAction]/#[TableGroupAction]'s display text.
     * module-table.blade.php used to hand $mainAction->label/$groupAction->name
     * straight to @lang('nexus::translate.' . $label) — only ever the
     * package's own shared dictionary, keyed by the label/name verbatim
     * ("Create", "deleteGroup", ...). That's fine for the framework's own
     * built-in actions (already in nexus::translate), but gave a
     * module-specific custom action (Restore's "Scan backups", this
     * package's own "Install from archive") nowhere to put its translation
     * except that same shared file — and a missing key there rendered the
     * raw "nexus::translate.Scan backups" string, not even the readable
     * label.
     *
     * This tries the module's own namespace first (so a module can add a new
     * action label or override a shared one), then the shared nexus::translate
     * exactly as before, and finally the raw label/name itself — each scope
     * checked both verbatim and lowercased, since several modules (Restore,
     * Backup) already ship lowercase keys ('scan backups', 'run now') that
     * this makes live for the first time rather than adding yet another
     * naming convention.
     */
    function nexus_trans_action(string $moduleName, string $label): string
    {
        if (str_contains($label, '::')) {
            return __($label);
        }

        $moduleNamespace = Str::lcfirst($moduleName);

        foreach ([$moduleNamespace, 'nexus'] as $namespace) {
            foreach ([$label, Str::lower($label)] as $key) {
                $fullKey = $namespace.'::translate.'.$key;
                if (Lang::has($fullKey)) {
                    return __($fullKey);
                }
            }
        }

        return $label;
    }
}

if (! function_exists('nexus_enum_display')) {

    /**
     * Format a #[Field(type: 'enum')]-cast attribute for display outside an
     * edit form (table cell, infolist, CSV export) — none of which have a
     * FieldConfigDto's enum section handy. An enum defining its own label()
     * (see PaymentStatus, RedirectStatusCode) is trusted to already return a
     * fully resolved, human string; anything else falls back to the flat
     * "{module}::translate.{value}" key convention (DemoStatus::DRAFT->value
     * === 'draft' matches translate.php's 'draft' key), and if even that's
     * missing, the raw backing value/name — never the literal untranslated
     * key string, and never the enum object itself (Blade's e() unwraps a
     * BackedEnum to ->value for display, but a raw (string) cast — e.g. CSV
     * export — throws on it outright).
     *
     * Non-enum values pass through unchanged, so call sites can run every
     * column/field value through this without a type check of their own.
     */
    function nexus_enum_display(mixed $value, string $moduleName): mixed
    {
        if (! ($value instanceof UnitEnum)) {
            return $value;
        }

        if (method_exists($value, 'label')) {
            return $value->label();
        }

        $raw = $value instanceof BackedEnum ? $value->value : $value->name;
        $key = Str::lcfirst($moduleName).'::translate.'.$raw;

        return Lang::has($key) ? __($key) : $raw;
    }
}

if (! function_exists('nexus_icon_html')) {

    /**
     * Render a #[Module]/#[Section] icon as HTML, picking the right markup
     * for whichever icon library the key belongs to. Most #[Module]/
     * #[Section] icon: values (e.g. "solar:cart-large-bold") are Iconify
     * "icon-set:name" identifiers and need the <iconify-icon> web component
     * (see the iconify-icon script tag in layouts/adminpanel.blade.php and
     * layouts/blank.blade.php) — a bare CSS class has no way to render them.
     * Everything else (a bare key like "edit", resolved via nexus_icon()
     * against the theme's own Boxicons map into e.g. "bx bx-pencil") wants a
     * plain <i class="...">.
     *
     * The Iconify check runs on the RAW key first, before nexus_icon() ever
     * sees it: IconManager::getIcon() falls back to $default for any key
     * it doesn't recognize (its Boxicons map never has "solar:..." entries),
     * which would otherwise silently replace a perfectly valid Iconify id
     * with the generic default icon instead of rendering it.
     */
    function nexus_icon_html(?string $key, ?string $module, string $default, string $classes = ''): string
    {
        $iconifyPattern = '/^[a-z0-9-]+:[a-z0-9-]+$/i';

        if ($key && preg_match($iconifyPattern, $key)) {
            return '<iconify-icon icon="'.e($key).'" class="'.e($classes).'"></iconify-icon>';
        }

        $resolved = nexus_icon($key, $module, $default);

        if ($resolved && preg_match($iconifyPattern, $resolved)) {
            return '<iconify-icon icon="'.e($resolved).'" class="'.e($classes).'"></iconify-icon>';
        }

        return '<i class="'.e(trim($resolved.' '.$classes)).'"></i>';
    }
}
