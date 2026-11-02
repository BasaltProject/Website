// Documentation enhancements. Everything re-runs on livewire:navigated, because
// wire:navigate swaps the page without reloading this module.

let savedSidebar = null;

function addCopyButtons() {
    document.querySelectorAll('.code-block').forEach((block) => {
        if (block.querySelector('.code-copy')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'code-copy';
        button.textContent = 'Copy';
        button.setAttribute('aria-label', 'Copy code to clipboard');
        block.appendChild(button);
    });
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest?.('.code-copy');
    if (!button) return;

    const code = button.closest('.code-block')?.querySelector('pre');
    if (!code) return;

    try {
        await navigator.clipboard.writeText(code.innerText.replace(/\n$/, ''));
        button.textContent = 'Copied';
        button.setAttribute('data-copied', '');
    } catch {
        button.textContent = 'Press Ctrl+C';
    }

    setTimeout(() => {
        button.textContent = 'Copy';
        button.removeAttribute('data-copied');
    }, 1600);
});

// "On this page": the active heading is the last one whose top is above 200px.
let spyScroll = null;

function stopScrollSpy() {
    if (spyScroll) {
        window.removeEventListener('scroll', spyScroll);
        window.removeEventListener('resize', spyScroll);
        spyScroll = null;
    }
}

function initScrollSpy() {
    stopScrollSpy();

    const links = [...document.querySelectorAll('[data-toc-link]')];
    if (!links.length) return;

    const entries = [];
    links.forEach((link) => {
        const heading = document.getElementById(decodeURIComponent(link.hash.slice(1)));
        if (heading) entries.push(heading);
    });
    if (!entries.length) return;

    const update = () => {
        const atEnd = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
        let current = null;

        if (atEnd) current = entries[entries.length - 1].id;
        else for (const heading of entries) {
            if (heading.getBoundingClientRect().top <= 200) current = heading.id;
        }

        links.forEach((link) => {
            if (current !== null && decodeURIComponent(link.hash.slice(1)) === current) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    };

    let frame = 0;
    spyScroll = () => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            update();
        });
    };

    window.addEventListener('scroll', spyScroll, { passive: true });
    window.addEventListener('resize', spyScroll, { passive: true });
    update();
}

// The timings viewer is a page of its own with a lot of code, so only that page loads it.
function mountTimings() {
    const root = document.querySelector('[data-timings-viewer]');
    if (root && !root.dataset.ready) import('./timings.js').then((module) => module.mount(root));
}

// Keep the sidebar where it was across wire:navigate; otherwise centre the active page.
document.addEventListener('livewire:navigating', () => {
    stopScrollSpy();
    const nav = document.querySelector('[data-sidebar-scroll]');
    savedSidebar = nav ? { section: nav.dataset.section, top: nav.scrollTop } : null;
});

function restoreSidebar() {
    const nav = document.querySelector('[data-sidebar-scroll]');

    if (nav && savedSidebar && savedSidebar.section === nav.dataset.section) {
        nav.scrollTop = savedSidebar.top;
    } else if (nav) {
        const active = nav.querySelector('[data-active]');
        if (active) {
            const offset = active.getBoundingClientRect().top - nav.getBoundingClientRect().top;
            nav.scrollTop += offset - nav.clientHeight / 3;
        }
    }

    savedSidebar = null;
}

function usesMac() {
    return /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
}

function init() {
    addCopyButtons();
    initScrollSpy();
    restoreSidebar();
    mountTimings();

    if (usesMac()) document.querySelectorAll('[data-kbd]').forEach((el) => (el.textContent = '⌘K'));
}

// Ctrl+K / Cmd+K focuses the docs search. On small screens the menu opens first.
document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'k') return;

    const inputs = [...document.querySelectorAll('[data-docs-search]')];
    if (!inputs.length) return;

    event.preventDefault();

    const visible = inputs.find((input) => input.offsetParent !== null);
    if (visible) return visible.focus();

    window.dispatchEvent(new CustomEvent('docs-open-menu'));
    setTimeout(() => inputs.find((input) => input.offsetParent !== null)?.focus(), 50);
});

document.addEventListener('livewire:navigated', init);
if (document.readyState !== 'loading') init();
else document.addEventListener('DOMContentLoaded', init);
