(() => {
    const root = document.querySelector('[data-user-support-chat]');
    if (!root) return;

    document.body.append(root);

    const toggle = root.querySelector('[data-chat-toggle]');
    const closeButton = root.querySelector('[data-chat-close]');
    const panel = root.querySelector('.support-chat-panel');
    const messages = root.querySelector('[data-chat-messages]');
    const status = root.querySelector('[data-chat-status]');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const sendButton = root.querySelector('[data-chat-send]');
    const csrfToken = root.dataset.csrfToken;
    const positionKey = `user-support-chat-position-${root.dataset.userId}`;
    let checking = false;
    let lastMessageId = null;
    let activePointer = null;
    let suppressClick = false;

    function setPosition(left, top) {
        const bounds = root.getBoundingClientRect();
        const maxLeft = Math.max(8, window.innerWidth - bounds.width - 8);
        const maxTop = Math.max(8, window.innerHeight - bounds.height - 8);
        root.style.left = `${Math.max(8, Math.min(left, maxLeft))}px`;
        root.style.top = `${Math.max(8, Math.min(top, maxTop))}px`;
        root.style.right = 'auto';
        root.style.bottom = 'auto';
    }

    function restorePosition() {
        try {
            const savedPosition = JSON.parse(window.localStorage.getItem(positionKey) || 'null');
            if (savedPosition && Number.isFinite(savedPosition.left) && Number.isFinite(savedPosition.top)) {
                setPosition(savedPosition.left, savedPosition.top);
            }
        } catch (error) {
            console.warn('Unable to restore the support chat position.', error);
        }
    }

    function savePosition() {
        try {
            window.localStorage.setItem(positionKey, JSON.stringify({
                left: root.getBoundingClientRect().left,
                top: root.getBoundingClientRect().top
            }));
        } catch (error) {
            console.warn('Unable to save the support chat position.', error);
        }
    }

    restorePosition();

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.method === 'POST' ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...options.headers
            },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!response.ok) throw new Error(`Support chat request failed with status ${response.status}.`);
        return response.json();
    }

    function renderMessages(items) {
        messages.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'support-chat-empty';
            empty.textContent = 'Send a message to start a conversation.';
            messages.append(empty);
            lastMessageId = null;
            return;
        }

        items.forEach(item => {
            const bubble = document.createElement('article');
            bubble.className = 'support-chat-bubble';
            if (item.is_mine) bubble.classList.add('is-mine');

            const sender = document.createElement('strong');
            sender.textContent = item.is_mine ? 'You' : 'Store support';
            const text = document.createElement('p');
            text.textContent = item.message;
            const time = document.createElement('time');
            time.dateTime = item.created_at;
            time.textContent = item.created_at ? new Date(item.created_at).toLocaleString() : '';
            bubble.append(sender, text, time);
            messages.append(bubble);
        });

        const newestMessageId = items[items.length - 1].id;
        if (newestMessageId !== lastMessageId) messages.scrollTop = messages.scrollHeight;
        lastMessageId = newestMessageId;
    }

    async function refresh() {
        if (checking || panel.hidden || document.visibilityState !== 'visible') return;

        checking = true;
        try {
            const payload = await request(root.dataset.endpoint);
            renderMessages(payload.messages || []);
            status.textContent = '';
        } finally {
            checking = false;
        }
    }

    function positionPanel() {
        const rootBounds = root.getBoundingClientRect();
        const panelBounds = panel.getBoundingClientRect();
        const maxLeft = window.innerWidth - panelBounds.width - 8;
        const panelLeft = Math.max(8, Math.min(rootBounds.right - panelBounds.width, maxLeft));

        panel.style.left = `${panelLeft - rootBounds.left}px`;
        panel.style.right = 'auto';
        panel.classList.toggle('is-below', rootBounds.top < panelBounds.height + 20);
    }

    function setOpen(open, focusInput = false) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) {
            window.requestAnimationFrame(positionPanel);
            refresh().catch(error => {
                console.error('Unable to load support chat.', error);
                status.textContent = 'Messages could not be loaded. Please try again.';
            });
            if (focusInput) input.focus();
        } else {
            toggle.focus();
        }
    }

    toggle.addEventListener('pointerdown', event => {
        if (event.button !== 0 || !event.isPrimary) return;
        const bounds = root.getBoundingClientRect();
        activePointer = {
            id: event.pointerId,
            x: event.clientX,
            y: event.clientY,
            left: bounds.left,
            top: bounds.top,
            moved: false
        };
        toggle.setPointerCapture(event.pointerId);
    });

    toggle.addEventListener('pointermove', event => {
        if (!activePointer || event.pointerId !== activePointer.id) return;
        const deltaX = event.clientX - activePointer.x;
        const deltaY = event.clientY - activePointer.y;
        if (!activePointer.moved && Math.hypot(deltaX, deltaY) < 6) return;

        activePointer.moved = true;
        setPosition(activePointer.left + deltaX, activePointer.top + deltaY);
        if (!panel.hidden) positionPanel();
    });

    toggle.addEventListener('pointerup', event => {
        if (!activePointer || event.pointerId !== activePointer.id) return;
        if (activePointer.moved) {
            suppressClick = true;
            savePosition();
        }
        activePointer = null;
    });

    toggle.addEventListener('pointercancel', () => {
        activePointer = null;
    });

    toggle.addEventListener('click', event => {
        if (suppressClick) {
            suppressClick = false;
            event.preventDefault();
            return;
        }
        setOpen(panel.hidden, event.detail === 0);
    });

    closeButton.addEventListener('click', () => setOpen(false));

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || sendButton.disabled) return;

        sendButton.disabled = true;
        status.textContent = '';
        try {
            await request(root.dataset.sendUrl, {
                method: 'POST',
                body: JSON.stringify({ message })
            });
            input.value = '';
            await refresh();
            input.focus();
        } catch (error) {
            console.error('Unable to send support chat message.', error);
            status.textContent = 'Your message could not be sent. Please try again.';
        } finally {
            sendButton.disabled = false;
        }
    });

    window.setInterval(() => {
        refresh().catch(error => {
            console.error('Unable to refresh support chat.', error);
            status.textContent = 'Messages could not be refreshed.';
        });
    }, 5000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && !panel.hidden) {
            refresh().catch(error => {
                console.error('Unable to refresh support chat.', error);
                status.textContent = 'Messages could not be refreshed.';
            });
        }
    });
    window.addEventListener('resize', () => {
        if (root.style.left) setPosition(root.getBoundingClientRect().left, root.getBoundingClientRect().top);
        if (!panel.hidden) positionPanel();
    });
})();
