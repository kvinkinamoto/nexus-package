<?php

namespace Nodex\Nexus\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Nodex\Nexus\Dto\FieldRenderContext;

/**
 * Central place to register a field type's rendering, without editing the
 * shared templates/sections/*.blade.php dispatch chain for every new type.
 *
 * Resolution order (see templates/sections/base.blade.php and
 * columns_2.blade.php), unchanged from before this registry existed:
 *   1. {module}::admin.field_types.{type}   — a module's own override, always wins
 *   2. this registry                        — registerView() / registerClass() / registerCallback()
 *   3. nexus::{template}.templates.field_types.{type} — built-in package partial
 *   4. .../field_types.unknown              — fallback warning banner
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
