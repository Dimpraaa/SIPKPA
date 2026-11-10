export function initTablePagination(root = document, browser = window) {
    let region = root.querySelector('[data-pagination-region]');
    if (!region || !browser.fetch || !browser.DOMParser || typeof AbortController === 'undefined') return () => {};

    const selector = `[data-pagination-region="${region.dataset.paginationRegion}"]`;
    const initialUrl = new URL(browser.location.href);
    const status = root.querySelector('[data-pagination-status]');
    let controller;
    let requestId = 0;

    function setLoading(loading) {
        region.setAttribute('aria-busy', String(loading));
        if (loading && status) status.textContent = 'Memuat halaman…';
    }

    async function load(url, pushHistory) {
        const id = ++requestId;
        controller?.abort();
        const requestController = new AbortController();
        controller = requestController;
        const timeout = setTimeout(() => requestController.abort(), 15000);
        setLoading(true);

        try {
            const response = await browser.fetch(url.href, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: requestController.signal,
            });
            if (id !== requestId) return;
            if (response.redirected) {
                browser.location.assign(url.href);
                return;
            }
            if (!response.ok) throw new Error('Halaman tidak tersedia.');

            const page = new browser.DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = page.querySelector(selector);
            if (!replacement) throw new Error('Bagian tabel tidak ditemukan.');
            if (id !== requestId) return;

            const position = { top: browser.scrollY, left: browser.scrollX };
            const horizontalOffsets = [...region.querySelectorAll('[role="region"]')].map((element) => element.scrollLeft);
            region.replaceWith(replacement);
            region = replacement;
            region.querySelectorAll('[role="region"]').forEach((element, index) => {
                element.scrollLeft = horizontalOffsets[index] ?? 0;
            });

            if (pushHistory) browser.history.pushState(browser.history.state, '', url.href);
            browser.scrollTo({ ...position, behavior: 'auto' });
            const navigation = region.querySelector('[data-pagination-controls] nav') ?? region;
            navigation.setAttribute('tabindex', '-1');
            navigation.focus({ preventScroll: true });
            if (status) status.textContent = 'Data halaman berhasil diperbarui.';
        } catch {
            if (id === requestId) {
                const error = region.querySelector('[data-pagination-error]');
                if (error) {
                    error.hidden = false;
                    error.querySelector('[data-pagination-retry]').href = url.href;
                    if (status) status.textContent = error.dataset?.paginationErrorMessage ?? 'Daftar peserta belum dapat dimuat. Silakan coba lagi.';
                } else {
                    browser.location.assign(url.href);
                }
            }
        } finally {
            clearTimeout(timeout);
            if (id === requestId) setLoading(false);
        }
    }

    function handleClick(event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest?.('[data-pagination-controls] a');
        if (!link || link.closest('[data-pagination-region]') !== region || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
        const url = new URL(link.href, browser.location.href);
        if (url.origin !== initialUrl.origin || url.pathname !== initialUrl.pathname) return;

        event.preventDefault();
        return load(url, true);
    }

    function handlePopState() {
        const url = new URL(browser.location.href);
        if (url.origin !== initialUrl.origin || url.pathname !== initialUrl.pathname) {
            browser.location.reload();
            return;
        }
        return load(url, false);
    }

    root.addEventListener('click', handleClick);
    browser.addEventListener('popstate', handlePopState);

    return () => {
        requestId++;
        controller?.abort();
        setLoading(false);
        root.removeEventListener('click', handleClick);
        browser.removeEventListener('popstate', handlePopState);
    };
}
