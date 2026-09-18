<?php

namespace Nodex\Nexus\Modules\BulkAction\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\Actions\Admin\CallGroupActionMethod;
use Nodex\Nexus\Services\Actions\Admin\DeleteActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\DuplicateActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\PublishActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\RestoreActionGroupMethod;
use Nodex\Nexus\Services\Actions\Admin\UnpublishActionGroupMethod;

/**
 * ModuleTable::runGroupAction() ran every *ActionGroupMethod synchronously,
 * in one request — fine for a handful of rows, not for hundreds (each row
 * fires its own before/after hooks and events). This chunks the same
 * selection through the exact same handler classes instead of
 * reimplementing their per-row delete/restore/publish/... logic, just
 * called once per chunk instead of once for the whole batch, with a
 * Cache-tracked progress the browser polls — same
 * nexus.module.export.progress endpoint MasterExportJob already uses for
 * this, since it's generic (reads whatever's at a given cache key, not
 * export-specific despite the route name).
 */
class BulkActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Mirrors ModuleTable::BUILT_IN_GROUP_ACTIONS — see that property's docblock. */
    private const BUILT_IN_GROUP_ACTIONS = [
        'deleteGroup' => DeleteActionGroupMethod::class,
        'restoreGroup' => RestoreActionGroupMethod::class,
        'publishGroup' => PublishActionGroupMethod::class,
        'unpublishGroup' => UnpublishActionGroupMethod::class,
        'duplicateGroup' => DuplicateActionGroupMethod::class,
    ];

    public function __construct(
        public string $moduleName,
        public string $actionName,
        public array $ids,
        public string $cacheKey,
        public ?int $userId,
        public int $chunkSize = 100,
    ) {}

    public function handle(): void
    {
        $total = count($this->ids);
        $this->putProgress(0, 0, $total, 'processing');

        $module = Module::findByName($this->moduleName);

        if (! $module) {
            $this->putProgress(0, 0, $total, 'failed');

            return;
        }

        $moduleConfig = $module->config;
        $actionGroupConfig = $moduleConfig->table->actionGroup[$this->actionName] ?? null;

        if (! isset(self::BUILT_IN_GROUP_ACTIONS[$this->actionName]) && $actionGroupConfig === null) {
            $this->putProgress(0, 0, $total, 'failed');

            return;
        }

        $processed = 0;

        foreach (array_chunk($this->ids, max(1, $this->chunkSize)) as $chunk) {
            // Same construction ModuleTable::runGroupAction() already uses
            // (Livewire\Concerns\CallsLegacyActionMethods::makeFormRequest())
            // — the *ActionGroupMethod classes read their validated 'items'
            // from a freshly container-resolved FormRequest, not from this
            // parameter directly, so merging into the shared request() here
            // is what actually threads the chunk through.
            request()->merge(['items' => $chunk]);
            /** @var FormRequest $formRequest */
            $formRequest = app(FormRequest::class);

            $this->withRealRedirector(function () use ($formRequest, $moduleConfig, $actionGroupConfig) {
                if (isset(self::BUILT_IN_GROUP_ACTIONS[$this->actionName])) {
                    self::BUILT_IN_GROUP_ACTIONS[$this->actionName]::handle($formRequest, $moduleConfig);
                } else {
                    CallGroupActionMethod::handle($formRequest, $moduleConfig, $actionGroupConfig);
                }
            });

            $processed += count($chunk);
            $this->putProgress((int) floor($processed * 100 / max(1, $total)), $processed, $total, 'processing');
        }

        $this->putProgress(100, $processed, $total, 'completed');
    }

    /**
     * QUEUE_CONNECTION=sync (the test suite's default) runs a dispatched job
     * immediately, inline, inside whatever request/component is currently
     * handling — including a Livewire request, whose 'redirect' container
     * binding returns Livewire\Features\SupportRedirects\Redirector instead
     * of a real Illuminate\Http\RedirectResponse from ->route(...). The
     * *ActionGroupMethod classes this job calls declare a strict
     * `: RedirectResponse` return type, so that mismatch is a TypeError, not
     * just a wrong redirect target — same problem, same fix, as
     * Livewire\Concerns\CallsLegacyActionMethods::withRealRedirector(),
     * which this job can't use directly (it isn't a Livewire component).
     * A real queue worker process never has Livewire's binding active in
     * the first place, so this is a safe no-op there.
     */
    private function withRealRedirector(\Closure $callback): mixed
    {
        $previousRedirector = app('redirect');

        $realRedirector = new \Illuminate\Routing\Redirector(app('url'));
        if (app()->bound('session.store')) {
            $realRedirector->setSession(app('session.store'));
        }
        app()->instance('redirect', $realRedirector);

        try {
            return $callback();
        } finally {
            app()->instance('redirect', $previousRedirector);
        }
    }

    private function putProgress(int $progress, int $processed, int $total, string $status): void
    {
        Cache::put($this->cacheKey, [
            'progress' => $progress,
            'processed' => $processed,
            'total' => $total,
            'status' => $status,
        ], now()->addHour());
    }
}
