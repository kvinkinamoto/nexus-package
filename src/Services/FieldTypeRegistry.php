<?php

namespace Nodex\Nexus\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Nodex\Nexus\Dto\FieldRenderContext;

/**
 * Central place to register a field type's rendering, without editing a
 * dispatch chain for every new type. Shared by BOTH themes' field-type
 * dispatchers — the legacy templates/sections/_field.blade.php and the
 * Livewire livewire/field_types/dispatch.blade.php — so a type registered
 * here (by a module's FieldTypes/ folder or a plugin's
 * 'nexus.field_types.register' hook) is available to whichever pipeline the
 * module actually renders through, instead of each pipeline needing its own
 * registration.
 *
 * Resolution order, identical in both dispatchers:
 *   1. {module}::admin.field_types.{type} (legacy) or
 *      {module}::admin.livewire_field_types.{type} (Livewire) — a module's
 *      own override, always wins, checked by the dispatcher itself before
 *      consulting this registry at all
 *   2. this registry                        — registerView() / registerClass() / registerCallback()
 *   3. the built-in partial at the type's own conventional path
 *      (.../templates.field_types.{type} or .../livewire.field_types.{type})
 *      — resolved via View::exists(), so a built-in type needs no
 *      registration here at all, only a file at that path
 *   4. .../field_types.unknown (legacy) or .../field_types/unsupported (Livewire) — fallback warning banner
 *
 * registerView() targets are @include'd the same way built-in/override
 * partials are — they inherit the calling Blade scope ($field, $model,
 * $module, $action, $tab_lang, $formData, $modelSchema, $errors, ...).
 * registerClass()/registerCallback() targets don't have a Blade scope to
 * inherit, so they receive an explicit FieldRenderContext instead.
 */
class FieldTypeRegistry
{
    /** @var array<string, string> type => view name */
    private array $views = [];

    /** @var array<string, class-string<\Nodex\Nexus\Contracts\FieldTypeRenderer>> type => renderer class */
    private array $classes = [];

    /** @var array<string, callable(FieldRenderContext): (View|string)> type => callback */
    private array $callbacks = [];

    /** @var array<string, string> alias => real type name */
    private array $aliases = [];

    /** @var array<string, string[]> type => default validation rules, consumed by Services/Validation/NexusRuleCollector.php */
    private array $defaultRules = [];

    public function registerView(string $type, string $view): void
    {
        $this->views[$this->resolveAlias($type)] = $view;
    }

    /**
     * @param  class-string<\Nodex\Nexus\Contracts\FieldTypeRenderer>  $class
     */
    public function registerClass(string $type, string $class): void
    {
        $this->classes[$this->resolveAlias($type)] = $class;
    }

    /**
     * @param  callable(FieldRenderContext): (View|string)  $callback
     */
    public function registerCallback(string $type, callable $callback): void
    {
        $this->callbacks[$this->resolveAlias($type)] = $callback;
    }

    /**
     * Makes $alias resolve to whatever $type is already (or will be)
     * registered under — e.g. alias('editor', 'text').
     */
    public function alias(string $alias, string $type): void
    {
        $this->aliases[$alias] = $type;
    }

    private function resolveAlias(string $type): string
    {
        return $this->aliases[$type] ?? $type;
    }

    /**
     * Public alias resolution, for a dispatcher to resolve $field->type once
     * up front (e.g. 'editor' -> 'text', 'date' -> 'birthday') and reuse the
     * result for every resolution step — registry lookups, the conventional
     * built-in-partial path, and validation defaults all agree on the same
     * real type name this way.
     */
    public function resolveType(string $type): string
    {
        return $this->resolveAlias($type);
    }

    /**
     * Baseline validation rules implied by a field type alone (e.g. 'email' ->
     * ['email']), applied before FieldConfigDto's own $rules/$storeRules/
     * $updateRules — see Services/Validation/NexusRuleCollector.php.
     */
    public function registerDefaultRules(string $type, array $rules): void
    {
        $this->defaultRules[$this->resolveAlias($type)] = $rules;
    }

    public function getDefaultRules(string $type): array
    {
        return $this->defaultRules[$this->resolveAlias($type)] ?? [];
    }

    /**
     * @return array<string, string> alias => real type name, for introspection (e.g. nexus:docs:field-types)
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return string[] every type with a registerView()/registerClass()/registerCallback() entry, for introspection (e.g. nexus:docs:field-types)
     */
    public function getRegisteredTypes(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->views),
            array_keys($this->classes),
            array_keys($this->callbacks),
        )));
    }

    /**
     * View-name resolution step (registry step 2a). Returns null if nothing
     * is registered for $type, or the registered view doesn't exist.
     */
    public function resolveViewName(string $type): ?string
    {
        $type = $this->resolveAlias($type);
        $view = $this->views[$type] ?? null;

        if ($view !== null && ViewFacade::exists($view)) {
            return $view;
        }

        return null;
    }

    /**
     * Class/callback resolution step (registry step 2b).
     */
    public function hasRenderer(string $type): bool
    {
        $type = $this->resolveAlias($type);

        return isset($this->classes[$type]) || isset($this->callbacks[$type]);
    }

    public function render(string $type, FieldRenderContext $context): View|string
    {
        $type = $this->resolveAlias($type);

        if (isset($this->classes[$type])) {
            return app($this->classes[$type])->render($context);
        }

        if (isset($this->callbacks[$type])) {
            return ($this->callbacks[$type])($context);
        }

        throw new \InvalidArgumentException("No renderer registered for field type [{$type}].");
    }
}
