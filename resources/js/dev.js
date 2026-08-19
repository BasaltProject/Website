// Progressive enhancement only: without JS every element stays visible and static.
document.documentElement.classList.add('js');

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

let observer = null;

// @persist keeps the same node, but re-attaching it restarts its CSS animations.
// Saving the offsets and putting them back is what keeps the background going.
let blobOffsets = [];

function backgroundAnimations() {
    if (typeof Element.prototype.getAnimations !== 'function') return [];

    return Array.from(document.querySelectorAll('.blob'))
        .map((el) => el.getAnimations()[0])
        .filter(Boolean);
}

function storeBackgroundProgress() {
    blobOffsets = backgroundAnimations().map((animation) => animation.currentTime);
}

function restoreBackgroundProgress() {
    if (!blobOffsets.length) return;

    const apply = () => {
        const animations = backgroundAnimations();
        animations.forEach((animation, index) => {
            if (blobOffsets[index] != null) animation.currentTime = blobOffsets[index];
        });

        return animations.length > 0;
    };

    // The re-attached node may not have had its animations created yet.
    if (!apply()) requestAnimationFrame(apply);
}

function revealOnScroll({ instant = false } = {}) {
    const targets = document.querySelectorAll('.reveal:not(.is-visible)');

    if (reduced.matches || !('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    observer ??= new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            // Stagger siblings so a row of cards arrives as a sequence, not a block.
            const delay = Number(entry.target.dataset.revealDelay ?? 0);
            setTimeout(() => entry.target.classList.add('is-visible'), delay);
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.05 });

    targets.forEach((el) => {
        // On a new page, what is already on screen appears at once: staggering it
        // would run under a <main> that is still invisible.
        if (instant && el.getBoundingClientRect().top < window.innerHeight) {
            el.classList.add('is-instant', 'is-visible');
            return;
        }

        observer.observe(el);
    });

    if (instant) {
        requestAnimationFrame(() => {
            document.querySelectorAll('.is-instant').forEach((el) => el.classList.remove('is-instant'));
        });
    }
}

// Cards track the pointer so the highlight reads as a reflection on the facet.
function trackPointerOnCards(event) {
    const card = event.target.closest?.('.card');
    if (!card) return;

    const rect = card.getBoundingClientRect();
    card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
    card.style.setProperty('--my', `${event.clientY - rect.top}px`);
}

function syncNavElevation() {
    const nav = document.querySelector('[data-nav]');
    if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 8);
}

function init(options) {
    revealOnScroll(options);
    syncNavElevation();
}

document.addEventListener('pointermove', trackPointerOnCards, { passive: true });
window.addEventListener('scroll', syncNavElevation, { passive: true });

document.addEventListener('livewire:navigating', storeBackgroundProgress);
document.addEventListener('livewire:navigated', () => {
    restoreBackgroundProgress();
    init({ instant: true });
});
document.addEventListener('DOMContentLoaded', () => init());

init();
