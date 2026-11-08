import assert from 'node:assert/strict';
import { test } from 'node:test';
import { initTablePagination } from '../resources/js/table-pagination.js';

const firstUrl = 'https://sipkpa.test/peserta?q=Andi&angkatan_id=3&page=1';
const secondUrl = 'https://sipkpa.test/peserta?q=Andi&angkatan_id=3&page=2';
const thirdUrl = 'https://sipkpa.test/peserta?q=Andi&angkatan_id=3&page=3';
const response = (body, options = {}) => ({ ok: true, redirected: false, text: async () => body, ...options });

test('an opted-in error state retains the previous table and retries the failed filtered page', async () => {
    let attempts = 0;
    const h = harness(async () => {
        if (++attempts === 1) throw new Error('Offline');
        return response('page-2');
    });
    const retry = { href: '' };
    const error = { hidden: true, querySelector: () => retry };
    const querySelector = h.root.region.querySelector;
    h.root.region.querySelector = (selector) => selector === '[data-pagination-error]' ? error : querySelector(selector);

    try {
        await h.click(secondUrl).result;

        assert.equal(h.root.region.name, 'page-1');
        assert.equal(h.root.region.attributes.get('aria-busy'), 'false');
        assert.equal(error.hidden, false);
        assert.equal(retry.href, secondUrl);
        assert.deepEqual(h.navigations, []);
        assert.deepEqual(h.pushes, []);
        assert.equal(h.status.textContent, 'Daftar peserta belum dapat dimuat. Silakan coba lagi.');

        await h.click(retry.href).result;

        assert.equal(h.root.region.name, 'page-2');
        assert.equal(h.status.textContent, 'Data halaman berhasil diperbarui.');
        assert.equal(h.pushes[0].url, secondUrl);
    } finally {
        h.stop();
    }
});

function harness(fetchPage = async (url) => response(`page-${new URL(url).searchParams.get('page')}`)) {
    const listeners = new Map();
    const browserListeners = new Map();
    const status = { textContent: '' };
    const requests = [];
    const pushes = [];
    const navigations = [];
    const scrolls = [];
    const pages = new Map();
    const root = {
        querySelector: (selector) => selector === '[data-pagination-region]' ? root.region : status,
        addEventListener: (event, callback) => listeners.set(event, callback),
        removeEventListener: (event) => listeners.delete(event),
    };

    for (const name of ['page-1', 'page-2', 'page-3']) {
        const attributes = new Map();
        const scroller = { scrollLeft: name === 'page-1' ? 120 : 0 };
        const region = {
            name, attributes, scroller,
            dataset: { paginationRegion: 'peserta' },
            setAttribute: (key, value) => attributes.set(key, value),
            querySelectorAll: () => [scroller],
            querySelector: (selector) => selector === '[data-pagination-error]' ? null : ({
                setAttribute() {},
                focus: (options) => { region.focusOptions = options; },
            }),
            replaceWith: (replacement) => { root.region = replacement; },
        };
        pages.set(name, region);
    }
    root.region = pages.get('page-1');

    const browser = {
        location: { href: firstUrl, assign: (url) => navigations.push(url), reload: () => navigations.push('reload') },
        scrollY: 640, scrollX: 0,
        scrollTo: (options) => scrolls.push(options),
        history: {
            state: { otherState: true },
            pushState(state, title, url) {
                pushes.push({ state, url });
                browser.location.href = url;
            },
        },
        fetch: (url, options) => {
            requests.push({ url, options });
            return fetchPage(url, options);
        },
        DOMParser: class {
            parseFromString(body) {
                return { querySelector: () => pages.get(body) ?? null };
            }
        },
        addEventListener: (event, callback) => browserListeners.set(event, callback),
        removeEventListener: (event) => browserListeners.delete(event),
    };

    function click(url, overrides = {}) {
        const link = { href: url, target: '', hasAttribute: () => false, closest: () => root.region };
        const event = {
            button: 0, defaultPrevented: false,
            target: { closest: () => link },
            preventDefault() { this.defaultPrevented = true; },
            ...overrides,
        };
        return { event, result: listeners.get('click')(event) };
    }

    const stop = initTablePagination(root, browser);
    return { root, browser, status, requests, pushes, navigations, scrolls, pages, click, browserListeners, stop };
}

test('pagination replaces only its region, retains filtered URL and scroll, and announces completion', async () => {
    const h = harness();
    try {
        const navigation = h.click(secondUrl);
        assert.equal(navigation.event.defaultPrevented, true);
        assert.equal(h.root.region.attributes.get('aria-busy'), 'true');

        await navigation.result;

        assert.equal(h.root.region.name, 'page-2');
        assert.equal(h.root.region.attributes.get('aria-busy'), 'false');
        assert.equal(h.root.region.scroller.scrollLeft, 120);
        assert.deepEqual(h.root.region.focusOptions, { preventScroll: true });
        assert.deepEqual(h.scrolls, [{ top: 640, left: 0, behavior: 'auto' }]);
        assert.equal(h.requests[0].url, secondUrl);
        assert.equal(h.requests[0].options.credentials, 'same-origin');
        assert.deepEqual(h.pushes, [{ state: { otherState: true }, url: secondUrl }]);
        assert.equal(h.status.textContent, 'Data halaman berhasil diperbarui.');
        assert.deepEqual(h.navigations, []);
    } finally {
        h.stop();
    }
});

test('rapid clicks abort the previous request and a late response cannot overwrite the newest page', async () => {
    const pending = [];
    const h = harness(() => new Promise((resolve) => pending.push(resolve)));
    try {
        const first = h.click(secondUrl).result;
        const latest = h.click(thirdUrl).result;
        assert.equal(h.requests[0].options.signal.aborted, true);
        assert.equal(h.requests[1].options.signal.aborted, false);

        pending[1](response('page-3'));
        await latest;
        pending[0](response('page-2'));
        await first;

        assert.equal(h.root.region.name, 'page-3');
        assert.equal(h.root.region.attributes.get('aria-busy'), 'false');
        assert.deepEqual(h.pushes.map((entry) => entry.url), [thirdUrl]);
        assert.deepEqual(h.navigations, []);
    } finally {
        h.stop();
    }
});

test('browser history restores table content without pushing an additional history entry', async () => {
    const h = harness();
    try {
        await h.click(secondUrl).result;
        h.browser.location.href = firstUrl;

        await h.browserListeners.get('popstate')();

        assert.equal(h.root.region.name, 'page-1');
        assert.equal(h.requests[1].url, firstUrl);
        assert.equal(h.pushes.length, 1);
        assert.deepEqual(h.navigations, []);
    } finally {
        h.stop();
    }
});

for (const [name, fetchPage] of [
    ['network failure', async () => { throw new Error('offline'); }],
    ['HTTP error', async () => response('', { ok: false })],
    ['authentication redirect', async () => response('', { redirected: true })],
    ['missing table region', async () => response('login page')],
]) {
    test(`pagination falls back to normal navigation after ${name}`, async () => {
        const h = harness(fetchPage);
        try {
            await h.click(secondUrl).result;
            assert.deepEqual(h.navigations, [secondUrl]);
            assert.equal(h.root.region.name, 'page-1');
            assert.equal(h.root.region.attributes.get('aria-busy'), 'false');
            assert.deepEqual(h.pushes, []);
        } finally {
            h.stop();
        }
    });
}

test('modified clicks and external links preserve standard browser navigation', () => {
    const h = harness();
    try {
        for (const modifier of ['ctrlKey', 'metaKey', 'shiftKey', 'altKey']) {
            assert.equal(h.click(secondUrl, { [modifier]: true }).event.defaultPrevented, false);
        }
        assert.equal(h.click(secondUrl, { button: 1 }).event.defaultPrevented, false);
        assert.equal(h.click('https://example.com/peserta?page=2').event.defaultPrevented, false);
        assert.deepEqual(h.requests, []);
    } finally {
        h.stop();
    }
});

test('pages without a pagination region need no listeners or network calls', () => {
    const root = { querySelector: () => null };
    assert.doesNotThrow(() => initTablePagination(root, {})());
});

test('forms added after initialization retain confirmation and busy behavior through delegated submission', async () => {
    const previousDocument = globalThis.document;
    const previousWindow = globalThis.window;
    const listeners = new Map();
    let accepted = false;
    globalThis.document = {
        querySelector: () => null,
        querySelectorAll: () => [],
        addEventListener: (name, callback) => listeners.set(name, callback),
    };
    globalThis.window = { confirm: () => accepted };

    try {
        await import('../resources/js/app.js');
        const button = { disabled: false, textContent: 'Simpan' };
        const form = {
            dataset: { confirm: 'Simpan perubahan?' },
            matches: (selector) => ['form[data-confirm]', 'form[data-busy]'].includes(selector),
            querySelectorAll: () => [button],
        };
        const event = () => ({ target: form, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } });

        const cancelled = event();
        listeners.get('submit')(cancelled);
        assert.equal(cancelled.defaultPrevented, true);
        assert.equal(button.disabled, false);

        accepted = true;
        const submitted = event();
        listeners.get('submit')(submitted);
        assert.equal(submitted.defaultPrevented, false);
        assert.equal(button.disabled, true);
        assert.equal(button.textContent, 'Sedang memproses…');
    } finally {
        if (previousDocument === undefined) delete globalThis.document;
        else globalThis.document = previousDocument;
        if (previousWindow === undefined) delete globalThis.window;
        else globalThis.window = previousWindow;
    }
});
