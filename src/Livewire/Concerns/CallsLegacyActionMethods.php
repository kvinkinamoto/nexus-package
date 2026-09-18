<?php

namespace Nodex\Nexus\Livewire\Concerns;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by every generic Livewire component that reuses the legacy
 * Services/Actions/Admin/*ActionMethod classes instead of duplicating their
 * business logic (see NexusController::action() — those classes are the same
 * ones the full-reload controller dispatches to).
 */
trait CallsLegacyActionMethods
{
    /**
     * The legacy action classes read their input via the globally bound
     * request() singleton (through GetModuleRequestAction resolving a
     * dedicated FormRequest subclass, or app(FormRequest::class) directly).
     * Livewire's own request is a JSON-wrapped update payload, not the raw
     * field data, so merging it into the bound request before resolving lets
     * FormRequestServiceProvider's createFrom() pick it up exactly as if it
     * had arrived in a real POST body.
     *
     * $recordId, when given, is merged in as a plain 'id' input key. A
     * dedicated Request's rules() sometimes reads $this->id for an update
     * (e.g. Rule::unique(...)->ignore($this->id)) — Illuminate\Http\Request's
     * __get() falls back to $this->route('id') only when 'id' isn't already
     * present in $this->all(), and the request being resolved here is bound
     * to Livewire's own internal update route (no {id} segment), so without
     * this the ignore() would silently receive null and the uniqueness check
     * would wrongly fire against the record's own row.
     */
    private function makeFormRequest(array $input, ?string $recordId = null): FormRequest
    {
        if ($recordId !== null && !array_key_exists('id', $input)) {
            $input['id'] = $recordId;
        }

        request()->merge($input);

        return app(FormRequest::class);
    }

    /**
     * While a Livewire component is handling a call, Livewire rebinds the
     * 'redirect' container entry to its own Redirector (see
     * Livewire\Features\SupportRedirects\SupportRedirects::boot()), whose
     * ->route()/->to() return the Redirector itself instead of an
     * Illuminate\Http\RedirectResponse. The legacy *ActionMethod classes this
     * trait's callers reuse declare a strict `: RedirectResponse` return type
     * on their internal `redirect()->route(...)` call, so invoking them as-is
     * inside a Livewire request throws a TypeError. None of these classes'
     * redirects are meaningful in a Livewire component anyway — the
     * component decides its own post-action navigation via $this->redirect()
     * — so this swaps in a real Redirector just for the duration of the
     * call, matching Laravel's own RoutingServiceProvider wiring, and lets
     * Livewire's binding resume immediately after.
     */
    private function withRealRedirector(\Closure $callback): mixed
    {
        $livewireRedirector = app('redirect');

        $realRedirector = new \Illuminate\Routing\Redirector(app('url'));
        if (app()->bound('session.store')) {
            $realRedirector->setSession(app('session.store'));
        }
        app()->instance('redirect', $realRedirector);

        try {
            return $callback();
        } finally {
            app()->instance('redirect', $livewireRedirector);
        }
    }
}
