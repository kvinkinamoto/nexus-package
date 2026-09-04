@extends('nexus::' . config('nexus.template') . '.layouts.adminpanel')

@section('contentPageCaption')
    @lang($module->name . '::' . 'translate.' . $module->name)
@endsection

@section('mainContent')
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            @lang($module->name . '::' . 'translate.index_title')
        </h2>
    </div>

    @livewire('nexus-module-table', ['moduleName' => $module->name])
@endsection

@section('js')
    @parent
    <script>
        function sendFormConfirm(shouldConfirm, action, message) {
            if (!shouldConfirm) {
                return true;
            }
            return confirm(message || ("Are you sure you want to " + action + "?"));
        }

        {{--
            Delegated (not bound per-element) so the listener survives
            Livewire re-rendering module-table.blade.php's own <input> on
            every morph — same convention resources/js/app.js already uses
            for the elFinder multi-file inputs, for the same reason.
        --}}
        document.addEventListener('change', function (e) {
            if (!e.target.id || !e.target.id.startsWith('nexusImportInput-')) {
                return;
            }

            const moduleName = e.target.id.replace('nexusImportInput-', '');
            const status = document.getElementById('nexusImportStatus-' + moduleName);
            const file = e.target.files[0];
            if (!file) {
                return;
            }

            if (status) {
                status.textContent = '@lang('nexus::translate.start')...';
            }

            const formData = new FormData();
            formData.append('file', file);

            fetch('{{ url(config('nexus.admin_prefix')) }}/' + moduleName + '/import', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
                body: formData,
            })
                .then((r) => r.json())
                .then((data) => {
                    if (status) {
                        status.textContent = data.message
                            || ('created: ' + (data.created ?? 0) + ', updated: ' + (data.updated ?? 0) + ', skipped: ' + (data.skipped ?? 0));
                    }
                    e.target.value = '';
                    window.Livewire?.find(e.target.closest('[wire\\:id]')?.getAttribute('wire:id'))?.call('$refresh');
                })
                .catch(() => {
                    if (status) {
                        status.textContent = '@lang('nexus::translate.import_error')';
                    }
                });
        });

        {{--
            Shared by export and bulk-group-actions below — both are queued
            jobs (MasterExportJob / BulkActionJob) that write
            {progress, processed, total, status} to the same cache-key shape,
            polled through nexus.module.export.progress (generic — it just
            reads whatever's at ?cacheKey=, nothing export-specific despite
            the route name). onDone(data)/onFailed() branch on what to do
            next, since export downloads a file and a bulk action just
            refreshes the table.
        --}}
        function pollNexusProgress(moduleName, cacheKey, statusEl, onDone, onFailed) {
            const baseUrl = '{{ url(config('nexus.admin_prefix')) }}/' + moduleName + '/export';

            const poll = function () {
                fetch(baseUrl + '/progress?cacheKey=' + encodeURIComponent(cacheKey), {
                    headers: {'Accept': 'application/json'},
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (data.status === 'completed') {
                            onDone(data);

                            return;
                        }

                        if (data.status === 'failed') {
                            if (statusEl) {
                                statusEl.textContent = '@lang('nexus::translate.alert.action_error')';
                            }
                            onFailed(data);

                            return;
                        }

                        if (statusEl) {
                            statusEl.textContent = (data.progress ?? 0) + '%';
                        }
                        setTimeout(poll, 1000);
                    })
                    .catch(() => {});
            };

            poll();
        }

        document.addEventListener('livewire:init', function () {
            {{--
                Export is queued (MasterExportJob) rather than synchronous
                like import, since it has to survive a full, possibly-large
                table scan — ModuleTable::exportTable() dispatches the job
                and hands back a cache key via this Livewire browser event.
            --}}
            Livewire.on('nexus-export-started', function (payload) {
                const {cacheKey, moduleName} = Array.isArray(payload) ? payload[0] : payload;
                const statusEl = document.getElementById('nexusExportStatus-' + moduleName);
                const baseUrl = '{{ url(config('nexus.admin_prefix')) }}/' + moduleName + '/export';

                pollNexusProgress(moduleName, cacheKey, statusEl, function (data) {
                    if (statusEl) {
                        statusEl.textContent = '@lang('nexus::translate.Export')';
                    }
                    if (data.filePath) {
                        window.location.href = baseUrl + '/download?filePath=' + encodeURIComponent(data.filePath);
                    }
                }, function () {});
            });

            {{--
                ModuleTable::dispatchAsyncBulkAction() — see its docblock for
                why only selections over ASYNC_BULK_ACTION_THRESHOLD go
                through this path instead of running synchronously.
            --}}
            Livewire.on('nexus-bulk-action-started', function (payload) {
                const {cacheKey, moduleName} = Array.isArray(payload) ? payload[0] : payload;
                const statusEl = document.getElementById('nexusBulkStatus-' + moduleName);

                pollNexusProgress(moduleName, cacheKey, statusEl, function (data) {
                    if (statusEl) {
                        statusEl.textContent = '';
                    }
                    const wireId = document.querySelector('[wire\\:id]')?.getAttribute('wire:id');
                    if (wireId) {
                        window.Livewire?.find(wireId)?.call('$refresh');
                    }
                }, function () {});
            });
        });
    </script>
@stop
