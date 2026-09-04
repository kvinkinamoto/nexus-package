<?php

namespace Nodex\Nexus\Modules\Export\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Nodex\Nexus\Dto\ModuleDtos\ColumnConfigDto;
use Nodex\Nexus\Events\ExportRowBuilding;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\TableBuilder;

/**
 * First real queued job in the package — NexusController::export() already
 * dispatched a job by this exact class name/constructor shape (resolved via
 * ModuleManager::nexus_module_class('Export', 'Jobs\MasterExportJob')), but
 * the class itself never existed, so calling export threw immediately. The
 * rest of the pipeline (progressExport's Cache::get poll, downloadExport's
 * Storage::disk('local') stream) was already correct and waiting for this.
 *
 * Reuses TableBuilder::build(..., sql: true) — the exact same filter/sort/
 * lens query TableTable's own index view applies — so "export the table"
 * genuinely means the table as the user currently has it filtered, not a
 * second, independent filtering implementation that could drift from the
 * real one.
 */
class MasterExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $moduleName,
        public string $cacheKey,
        public array $requestData,
        public ?int $userId,
        public ?int $chunkSize = 5000,
    ) {}

    public function handle(): void
    {
        $this->putProgress(0, 0, 'processing');

        $module = Module::findByName($this->moduleName);

        if (! $module) {
            $this->putProgress(0, 0, 'failed');

            return;
        }

        $built = app(TableBuilder::class)->build($module, $this->requestData, sql: true);

        if (! $built) {
            $this->putProgress(0, 0, 'failed');

            return;
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $built['query'];
        /** @var ColumnConfigDto[] $columns */
        $columns = $built['columns'];

        $total = (clone $query)->count();
        $chunkSize = $this->chunkSize && $this->chunkSize > 0 ? $this->chunkSize : 5000;

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, array_map(fn (ColumnConfigDto $column) => $column->label ?? $column->name, $columns));

        $processed = 0;
        $query->chunk($chunkSize, function ($rows) use ($stream, $columns, &$processed, $total) {
            foreach ($rows as $row) {
                $line = array_map(
                    fn (ColumnConfigDto $column) => (string) ($row->{$column->fieldName ?? $column->name} ?? ''),
                    $columns,
                );

                // Lets a plugin reformat a value (dates, lookups, computed
                // columns) or redact one for export without a bespoke export
                // pipeline per module.
                event(new ExportRowBuilding($row, $line, $this->moduleName));
                fputcsv($stream, nexus_filter('nexus.export.row', $line, $row, $this->moduleName));
                $processed++;
            }

            $this->putProgress($total > 0 ? (int) floor($processed * 100 / $total) : 100, $processed, 'processing');
        });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $filePath = 'exports/'.$this->moduleName.'_'.now()->format('Ymd_His').'_'.substr($this->cacheKey, -6).'.csv';
        Storage::disk('local')->put($filePath, $csv);

        $this->putProgress(100, $processed, 'completed', $filePath);
    }

    private function putProgress(int $progress, int $processed, string $status, ?string $filePath = null): void
    {
        Cache::put($this->cacheKey, array_filter([
            'progress' => $progress,
            'processed' => $processed,
            'status' => $status,
            'filePath' => $filePath,
        ], fn ($value) => $value !== null), now()->addHour());
    }
}
