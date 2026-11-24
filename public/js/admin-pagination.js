(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const behavior = reducedMotion ? 'auto' : 'smooth';

    document.querySelectorAll('[data-pagination-window]').forEach(viewport => {
        const currentPage = viewport.querySelector('[aria-current="page"]');
        if (!currentPage) return;

        const viewportBounds = viewport.getBoundingClientRect();
        const currentBounds = currentPage.getBoundingClientRect();
        const targetLeft = viewport.scrollLeft
            + currentBounds.left
            - viewportBounds.left
            - (viewport.clientWidth - currentBounds.width) / 2;
        const maxLeft = viewport.scrollWidth - viewport.clientWidth;

        viewport.scrollTo({
            left: Math.max(0, Math.min(targetLeft, maxLeft)),
            behavior
        });
    });
})();
