(function () {
    const button = document.querySelector('.account-button');
    const menu = document.getElementById('accountMenu');
    if (!button || !menu) return;

    function setOpen(open) {
        menu.hidden = !open;
        button.setAttribute('aria-expanded', String(open));
    }

    button.addEventListener('click', () => setOpen(menu.hidden));

    document.addEventListener('click', event => {
        if (!event.target.closest('.account-actions')) setOpen(false);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !menu.hidden) {
            setOpen(false);
            button.focus();
        }
    });

    const logoutForm = document.querySelector('[data-user-logout]');
    const logoutDialog = document.querySelector('[data-user-logout-dialog]');
    if (!logoutForm || !logoutDialog) return;

    const confirmLogout = logoutDialog.querySelector('[data-user-logout-confirm]');
    let confirmedLogout = false;

    logoutForm.addEventListener('submit', event => {
        if (confirmedLogout) {
            confirmedLogout = false;
            return;
        }

        event.preventDefault();
        logoutDialog.showModal();
    });

    logoutDialog.querySelector('[data-user-logout-cancel]').addEventListener('click', () => logoutDialog.close());
    confirmLogout.addEventListener('click', () => {
        confirmedLogout = true;
        logoutDialog.close();
        logoutForm.requestSubmit();
    });
    logoutDialog.addEventListener('click', event => {
        if (event.target === logoutDialog) logoutDialog.close();
    });
})();
