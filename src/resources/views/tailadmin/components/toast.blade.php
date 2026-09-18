@php
    /*
     * Same 'error-*' Tailwind scale the field-level @error() partials use
     * (e.g. livewire/field_types/string.blade.php's "text-error-500") — one
     * red language for "something is wrong" across the whole admin UI,
     * instead of failures only having a place to show up as red on a form
     * field and nowhere else. 'warning' stays its own amber state for
     * non-failure notices (e.g. impersonation, restore).
     */
    $type = $type ?? 'success';
    $icon = $type === 'success' ? nexus_icon('success') : nexus_icon('error');

    /*
     * Full literal class strings per branch, not bg-{{ $palette }}-50 style
     * interpolation — Tailwind's JIT scanner only picks up a utility class
     * that appears as a literal string somewhere in a scanned source file
     * (see .ai/rules/views.md); a runtime-built class name is silently never
     * generated.
     */
    $bgClass = match ($type) {
        'error' => 'bg-error-50 dark:bg-error-500/10',
        'warning' => 'bg-warning-50 dark:bg-warning-500/10',
        default => 'bg-success-50 dark:bg-success-500/10',
    };
    $iconColorClass = match ($type) {
        'error' => 'text-error-600',
        'warning' => 'text-warning-600',
        default => 'text-success-600',
    };
    $labelColorClass = match ($type) {
        'error' => 'text-error-700 dark:text-error-400',
        'warning' => 'text-warning-700 dark:text-warning-400',
        default => 'text-success-700 dark:text-success-400',
    };
@endphp

<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, {{ config('nexus.toast.delay') }})"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed bottom-4 right-4 z-999999 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between gap-2 px-4 py-3 {{ $bgClass }}">
        <div class="flex items-center gap-2">
            <i class="{{ $icon }} text-lg {{ $iconColorClass }}"></i>
            <span class="text-xs font-semibold uppercase {{ $labelColorClass }}">
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
