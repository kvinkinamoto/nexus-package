{{-- Standalone auth pages have no storefront/admin layout: this provides the JSON fetch helper
     and Alpine (bundled with Livewire, so no extra JS entry or CDN is needed). --}}
<script>
window.authFetch = async function authFetch(url, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token ?? '',
            ...(options.headers ?? {}),
        },
        body: options.body ? JSON.stringify(options.body) : undefined,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'Request error');
        error.data = data;
        error.status = response.status;
        throw error;
    }

    return data;
};
</script>
@livewireScripts