(() => {
    const root = document.querySelector('[data-admin-support-chat]');
    if (!root) return;

    const conversationList = root.querySelector('[data-chat-conversations]');
    const conversationTitle = root.querySelector('[data-chat-customer]');
    const messages = root.querySelector('[data-chat-messages]');
    const status = root.querySelector('[data-chat-status]');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const sendButton = root.querySelector('[data-chat-send]');
    const csrfToken = root.dataset.csrfToken;
    let conversations = [];
    let selectedConversationId = null;
    let lastMessageId = null;
    let checking = false;

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

    function renderConversations() {
        conversationList.replaceChildren();
        if (!conversations.length) {
            const empty = document.createElement('p');
            empty.className = 'admin-chat-empty-list';
            empty.textContent = 'No customer conversations yet.';
            conversationList.append(empty);
            return;
        }

        conversations.forEach(conversation => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'admin-chat-conversation';
            button.setAttribute('aria-pressed', String(String(conversation.id) === String(selectedConversationId)));
            if (String(conversation.id) === String(selectedConversationId)) button.classList.add('is-active');

            const topLine = document.createElement('span');
            topLine.className = 'admin-chat-conversation-top';
            const name = document.createElement('strong');
            name.textContent = conversation.customer;
            topLine.append(name);
            if (conversation.unread_count > 0) {
                const unread = document.createElement('span');
                unread.className = 'admin-chat-unread';
                unread.textContent = conversation.unread_count > 99 ? '99+' : String(conversation.unread_count);
                topLine.append(unread);
            }

            const preview = document.createElement('span');
            preview.className = 'admin-chat-preview';
            preview.textContent = conversation.preview;
            const time = document.createElement('time');
            time.dateTime = conversation.updated_at || '';
            time.textContent = conversation.updated_at ? new Date(conversation.updated_at).toLocaleString() : '';
            button.append(topLine, preview, time);
            button.addEventListener('click', () => {
                if (String(selectedConversationId) === String(conversation.id)) return;
                selectedConversationId = conversation.id;
                lastMessageId = null;
                renderConversations();
                refresh().catch(showError);
            });
            conversationList.append(button);
        });
    }

    function renderMessages(items) {
        messages.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'admin-chat-empty-message';
            empty.textContent = 'No messages in this conversation yet.';
            messages.append(empty);
            lastMessageId = null;
            return;
        }

        items.forEach(item => {
            const bubble = document.createElement('article');
            bubble.className = 'admin-chat-bubble';
            if (item.is_mine) bubble.classList.add('is-mine');

            const sender = document.createElement('strong');
            sender.textContent = item.is_mine ? `You · ${item.sender_name}` : item.sender_name;
            const role = document.createElement('span');
            const isCustomer = item.sender_role === 'customer';
            role.className = `admin-chat-role ${isCustomer ? 'is-customer' : 'is-support'}`;
            role.textContent = isCustomer ? 'Customer' : 'Support';
            const senderLine = document.createElement('div');
            senderLine.className = 'admin-chat-sender-line';
            senderLine.append(sender, role);
            const text = document.createElement('p');
            text.textContent = item.message;
            const time = document.createElement('time');
            time.dateTime = item.created_at || '';
            time.textContent = item.created_at ? new Date(item.created_at).toLocaleString() : '';
            bubble.append(senderLine, text, time);
            messages.append(bubble);
        });

        const newestMessageId = items[items.length - 1].id;
        if (newestMessageId !== lastMessageId) messages.scrollTop = messages.scrollHeight;
        lastMessageId = newestMessageId;
    }

    async function refresh() {
        if (checking || document.visibilityState !== 'visible') return;

        checking = true;
        try {
            const payload = await request(root.dataset.conversationsUrl);
            conversations = payload.conversations || [];
            if (!conversations.some(item => String(item.id) === String(selectedConversationId))) {
                selectedConversationId = conversations[0]?.id || null;
                lastMessageId = null;
            }
            renderConversations();

            if (!selectedConversationId) {
                conversationTitle.textContent = 'Select a conversation';
                const empty = document.createElement('p');
                empty.className = 'admin-chat-empty-message';
                empty.textContent = 'Customer messages will appear here.';
                messages.replaceChildren(empty);
                form.hidden = true;
                return;
            }

            const selected = conversations.find(item => String(item.id) === String(selectedConversationId));
            conversationTitle.textContent = selected ? selected.customer : 'Support conversation';
            form.hidden = false;
            const url = root.dataset.messagesUrlTemplate.replace('__CONVERSATION_ID__', encodeURIComponent(selectedConversationId));
            const messagePayload = await request(url);
            renderMessages(messagePayload.messages || []);
            status.textContent = '';
        } finally {
            checking = false;
        }
    }

    function showError(error) {
        console.error('Unable to load support conversations.', error);
        status.textContent = 'Conversations could not be loaded. Please try again.';
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const message = input.value.trim();
        if (!selectedConversationId || !message || sendButton.disabled) return;

        sendButton.disabled = true;
        status.textContent = '';
        try {
            const url = root.dataset.sendUrlTemplate.replace('__CONVERSATION_ID__', encodeURIComponent(selectedConversationId));
            await request(url, {
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

    refresh().catch(showError);
    window.setInterval(() => {
        refresh().catch(showError);
    }, 5000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refresh().catch(showError);
    });
})();
