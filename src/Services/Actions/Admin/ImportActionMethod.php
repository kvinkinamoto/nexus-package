<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nodex\Nexus\Dto\ModuleDtos\ColumnConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Events\ImportRowBuilding;

/**
 * v1-scoped CSV import: synchronous (no queue/progress-poll, unlike export —
 * import files are typically far smaller than full-table exports, so
 * doubling the async infrastructure for both directions wasn't worth it for
 * a first version). Deliberately opt-in per module via #[TableImport] (see
 * that attribute's docblock) rather than auto-enabled like export, since
 * this writes data.
 *
 * Maps CSV header cells to #[Column]-declared fields by label or raw name,
 * then updateOrCreate's each row against the model's own $fillable — a bad
 * row (DB constraint violation, etc.) is skipped rather than aborting the
 * whole file, since one malformed line in a large CSV shouldn't lose every
 * other row's import.
 */
class ImportActionMethod
{
    public static function handle(Request $request, DefaultModuleConfigurationDto $moduleConfig): JsonResponse
    {
        $importConfig = collect($moduleConfig->table->imports ?? [])->first(fn ($import) => $import->isActive);

        if (!$importConfig) {
            return response()->json(['message' => 'Import is not enabled for this module.'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $modelClass = $moduleConfig->model;
        $fillable = array_flip((new $modelClass())->getFillable());

        $columnsByLabel = collect($moduleConfig->table->columns ?? [])->keyBy(fn (ColumnConfigDto $c) => $c->label);
        $columnsByName = collect($moduleConfig->table->columns ?? [])->keyBy(fn (ColumnConfigDto $c) => $c->name);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle, escape: '\\');

        if ($header === false) {
            fclose($handle);

            return response()->json(['created' => 0, 'updated' => 0, 'skipped' => 0]);
        }

        $fieldByIndex = [];
        foreach ($header as $index => $cell) {
            $cell = trim((string) $cell);
            /** @var ColumnConfigDto|null $column */
            $column = $columnsByLabel->get($cell) ?? $columnsByName->get($cell);

            if ($column) {
                $fieldByIndex[$index] = $column->fieldName ?? $column->name;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if ($row === [null]) {
                continue;
            }

            $rowId = null;
            $data = [];

            foreach ($fieldByIndex as $index => $fieldName) {
                $value = $row[$index] ?? null;

                if ($fieldName === 'id') {
                    $rowId = $value !== '' ? $value : null;
                    continue;
                }

                $data[$fieldName] = $value;
            }

            // Before the fillable intersect below, so a plugin can transform
            // a value (reformat a date, map an external id, ...) but can
            // never inject a field the model doesn't already allow mass
            // assignment of.
            event(new ImportRowBuilding($data, $moduleConfig));
            $data = nexus_filter('nexus.import.row', $data, $moduleConfig);
            $data = array_intersect_key($data, $fillable);

            if (empty($data)) {
                $skipped++;
                continue;
            }

            try {
                $model = $rowId ? $modelClass::find($rowId) : null;

                if ($model) {
                    $model->update($data);
                    $updated++;
                } else {
                    $modelClass::create($data);
                    $created++;
                }
            } catch (\Throwable) {
                $skipped++;
            }
        }

        fclose($handle);

        return response()->json(['created' => $created, 'updated' => $updated, 'skipped' => $skipped]);
    }
}
