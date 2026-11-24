(() => {
    const dialog = document.querySelector('[data-chat-confirm-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const title = dialog.querySelector('[data-chat-confirm-title]');
    const message = dialog.querySelector('[data-chat-confirm-message]');
    const cancelButton = dialog.querySelector('[data-chat-confirm-cancel]');
    const confirmButton = dialog.querySelector('[data-chat-confirm-proceed]');
    let resolveConfirmation = null;

    dialog.addEventListener('close', () => {
        if (!resolveConfirmation) return;
        resolveConfirmation(dialog.returnValue === 'confirm');
        resolveConfirmation = null;
    });

    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close('cancel');
    });

    cancelButton.addEventListener('click', () => dialog.close('cancel'));
    confirmButton.addEventListener('click', () => dialog.close('confirm'));

    window.showChatConfirmation = options => {
        if (dialog.open) return Promise.resolve(false);

        title.textContent = options.title;
        message.textContent = options.message;
        confirmButton.textContent = options.confirmLabel || 'Delete';

        return new Promise(resolve => {
            resolveConfirmation = resolve;
            dialog.showModal();
            cancelButton.focus();
        });
    };
})();
