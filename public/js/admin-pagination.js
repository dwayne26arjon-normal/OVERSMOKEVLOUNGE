(() => {
    document.querySelectorAll('[data-pagination-window]').forEach(viewport => {
        const pageName = viewport.dataset.pageName || 'page';
        const currentUrl = new URL(window.location.href);
        const currentPage = Number(currentUrl.searchParams.get(pageName)) || 1;
        currentUrl.searchParams.delete(pageName);
        const storageKey = `admin-pagination:${currentUrl.pathname}:${currentUrl.search}`;

        try {
            const previousNavigation = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
            if (previousNavigation && previousNavigation.to === currentPage) {
                viewport.classList.add(previousNavigation.to > previousNavigation.from
                    ? 'is-sliding-forward'
                    : 'is-sliding-backward');
            }
            sessionStorage.removeItem(storageKey);
        } catch (error) {
            console.warn('Pagination animation is unavailable.', error);
        }

        viewport.closest('.admin-pagination').querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', () => {
                const targetUrl = new URL(link.href, window.location.href);
                const targetPage = Number(targetUrl.searchParams.get(pageName)) || 1;
                if (targetPage === currentPage) return;

                try {
                    sessionStorage.setItem(storageKey, JSON.stringify({
                        from: currentPage,
                        to: targetPage
                    }));
                } catch (error) {
                    console.warn('Pagination animation is unavailable.', error);
                }
            });
        });
    });
})();
