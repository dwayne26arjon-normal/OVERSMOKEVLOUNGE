(() => {
    const root = document.querySelector('[data-user-support-chat]');
    if (!root) return;

    const toggle = root.querySelector('[data-chat-toggle]');
    const closeButton = root.querySelector('[data-chat-close]');
    const panel = root.querySelector('.support-chat-panel');
    const messages = root.querySelector('[data-chat-messages]');
    const status = root.querySelector('[data-chat-status]');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const sendButton = root.querySelector('[data-chat-send]');
    const csrfToken = root.dataset.csrfToken;
    let checking = false;
    let lastMessageId = null;

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

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) {
            refresh().catch(error => {
                console.error('Unable to load support chat.', error);
                status.textContent = 'Messages could not be loaded. Please try again.';
            });
        }
    }

    toggle.addEventListener('click', () => setOpen(panel.hidden));
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
})();
