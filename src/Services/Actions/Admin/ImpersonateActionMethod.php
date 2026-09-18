<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;

class ImpersonateActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): RedirectResponse
    {
        $modelClass = $moduleConfig->model;
        $target = $modelClass::query()->findOrFail($id);
        $current = auth()->user();

        if ((string) $target->getKey() === (string) $current->getKey()) {
            return redirect()->route('nexus.admin')
                ->with('alert_message', __('nexus::translate.cannot_impersonate_self'))
                ->with('alert_type', 'warning');
        }

        // A target who can themselves reach the admin panel is a peer, not
        // someone whose account needs support-style "see what they see" —
        // impersonating another admin also opens an easy privilege-escalation
        // path (admin A impersonates admin B, does something as B).
        if ($target->hasPermissionTo(AdminPanelPermissionEnum::ADMIN_PANEL->value)) {
            return redirect()->route('nexus.admin')
                ->with('alert_message', __('nexus::translate.cannot_impersonate_admin'))
                ->with('alert_type', 'warning');
        }

        // Set before loginUsingId(): the guard's own session ID regeneration
        // only migrates the session's *identity* (via session_regenerate_id),
        // the in-memory attribute bag — including this key — carries over.
        session(['nexus_impersonator_id' => $current->getKey()]);

        activity('impersonation')
            ->causedBy($current)
            ->performedOn($target)
            ->log('impersonation_started');

        Auth::guard('web')->loginUsingId($target->getKey());

        // The target is guaranteed (by the guard above) to lack ADMIN_PANEL,
        // so sending them to /admin would just bounce off NexusAdminMiddleware
        // immediately — land on the storefront instead, since "see the site
        // as this customer" is the actual point of impersonating one.
        return redirect()->route('shop.home')
            ->with('alert_message', __('nexus::translate.now_impersonating', ['name' => $target->name ?? $target->email]))
            ->with('alert_type', 'success');
    }
}
