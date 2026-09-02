@php
    $type = $type ?? 'success';
    $isWarning = $type === 'warning';
    $icon = $isWarning ? nexus_icon('error') : nexus_icon('success');
@endphp

<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, {{ config('nexus.toast.delay') }})"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed bottom-4 right-4 z-999999 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between gap-2 px-4 py-3 {{ $isWarning ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10' }}">
        <div class="flex items-center gap-2">
            <i class="{{ $icon }} text-lg {{ $isWarning ? 'text-warning-600' : 'text-success-600' }}"></i>
            <span class="text-xs font-semibold uppercase {{ $isWarning ? 'text-warning-700 dark:text-warning-400' : 'text-success-700 dark:text-success-400' }}">
                {{ $type }}
            </span>
        </div>
        <button type="button" @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <i class="bx bx-x text-base"></i>
        </button>
    </div>
    <div class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
        {{ $message }}
    </div>
</div>
