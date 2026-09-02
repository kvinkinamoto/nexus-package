<main class="flex-1">
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        @yield('mainContent')
    </div>
</main>

<footer class="border-t border-gray-200 px-4 py-4 text-sm text-gray-500 md:px-6 dark:border-gray-800 dark:text-gray-400">
    <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
        <span>&copy; {{ date('Y') }}</span>
        <span>
            Made by
            <a href="https://nodexsoft.com" class="font-medium text-gray-700 hover:text-brand-500 dark:text-gray-300" target="_blank" rel="noopener">Nodex</a>
        </span>
    </div>
</footer>
