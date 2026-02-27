@extends('dashboards.index')

@section('content')
<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="bg-light rounded h-100 p-4" style="height: 75vh;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0"><i class="fa fa-users me-2"></i>Members</h6>
                    <span class="text-muted small">Tenant</span>
                </div>
                <input type="text" id="memberSearch" class="form-control mb-3" placeholder="Search members" />
                <div id="memberSearchStatus" class="form-text small text-muted"></div>
                <style>
                    /* Active member highlight consistent with primary blue */
                    .member-item.active {
                        background-color: #0d6efd !important;
                        color: #fff !important;
                    }
                    .member-item.active .member-name { color: #fff !important; }

                    /* Smooth themed scrollbars */
                    #memberList, #messages { scrollbar-width: thin; scrollbar-color: #ced4da transparent; }
                    #memberList::-webkit-scrollbar, #messages::-webkit-scrollbar { width: 8px; }
                    #memberList::-webkit-scrollbar-thumb, #messages::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 8px; }
                    #memberList::-webkit-scrollbar-thumb:hover, #messages::-webkit-scrollbar-thumb:hover { background: #ced4da; }
                </style>
                <ul id="memberList" class="list-group list-group-flush" style="max-height: 60vh; overflow-y: auto;">
                    @foreach($members as $m)
                        <li class="list-group-item d-flex align-items-center member-item" data-user-id="{{ $m->id }}" style="cursor: pointer;">
                            <img class="rounded-circle me-3" src="{{ isset($m->userDetail) && $m->userDetail->avatar ? asset('uploads/avatars/' . $m->userDetail->avatar) : asset('avatar.jpeg') }}" alt="avatar" style="width: 32px; height: 32px;">
                            <span class="member-name">{{ $m->name }}</span>
                            @if(isset($m->unread_count) && $m->unread_count > 0)
                                <span class="unread-dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#0d6efd;margin-left:8px;"></span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="bg-light rounded h-100 p-4 d-flex flex-column" style="height: 75vh; max-height: 75vh;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0"><i class="fa fa-comments me-2"></i>Conversations</h6>
                    <span id="activePeerName" class="text-muted small"></span>
                </div>

                <div id="messages" class="flex-grow-1 border rounded p-3 mb-3" style="background:#fff; overflow-y:auto;">
                    <div class="text-muted text-center">Select a member to start chatting.</div>
                </div>

                <form id="composer" class="mt-auto">
                    <div class="mb-2">
                        <textarea id="messageBody" class="form-control" rows="2" placeholder="Type a message"></textarea>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="file" id="attachments" multiple class="form-control" style="max-width: 50%;" />
                        <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane me-1"></i>Send</button>
                    </div>
                    <div id="composerHint" class="form-text mt-1">Attachments limited to 25MB each. Allowed: images, videos, PDF, DOC/DOCX, XLS/XLSX.</div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = '{{ csrf_token() }}';
    let activeConversationId = null;
    let activePeerUserId = null;
    let activePeerName = '';

    function formatMessage(msg) {
        const you = {{ $authUser->id }} === msg.sender_id;
        const name = msg.sender?.name || (you ? 'You' : '');
        let attachmentsHtml = '';
        if (Array.isArray(msg.attachments) && msg.attachments.length) {
            attachmentsHtml = msg.attachments.map(att => {
                const url = att.secure_url || att.url;
                const rt = att.resource_type;
                const name = att.original_name || 'Attachment';
                const mime = att.mime_type || '';
                if (rt === 'image') {
                    return `<div class="mt-2"><img src="${url}" alt="image" style="max-width: 200px;" /></div>`;
                } else if (rt === 'video') {
                    return `<div class="mt-2"><video controls style="max-width: 240px;"><source src="${url}" type="${mime}"></video></div>`;
                } else {
                    const icon = mime.includes('pdf') ? 'fa-file-pdf' :
                        (mime.includes('word') || mime.includes('msword')) ? 'fa-file-word' :
                        (mime.includes('sheet') || mime.includes('excel')) ? 'fa-file-excel' :
                        'fa-file';
                    return `<div class="mt-2"><i class="fa ${icon} me-2 text-muted"></i><a href="${url}" target="_blank" rel="noopener" download>${name}</a></div>`;
                }
            }).join('');
        }
        const statusIcon = you
            ? (msg.read_by_recipient ? '<i class="fa fa-check-double" style="color:#0d6efd"></i>' : (msg.delivered ? '<i class="fa fa-check text-muted"></i>' : ''))
            : '';
        return `
            <div class="mb-3">
                <div class="d-flex ${you ? 'justify-content-end' : 'justify-content-start'}">
                    <div class="p-2 rounded" style="background:${you ? '#e7f1ff' : '#f8f9fa'}; max-width: 75%;">
                        ${name ? `<div class="text-muted small">${name}</div>` : ''}
                        ${msg.body ? `<div>${msg.body}</div>` : ''}
                        ${attachmentsHtml}
                        <div class="text-muted small mt-1 d-flex align-items-center ${you ? 'justify-content-between' : ''}">
                            <span>${new Date(msg.created_at).toLocaleString()}</span>
                            ${you ? `<span class="ms-2">${statusIcon}</span>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderMessages(list, reset = true) {
        const container = document.getElementById('messages');
        if (reset) container.innerHTML = '';
        list.forEach(m => container.insertAdjacentHTML('beforeend', formatMessage(m)));
        container.scrollTop = container.scrollHeight;
    }

    async function ensureConversation(otherUserId) {
        const res = await fetch("{{ route('conversations.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: (() => { const fd = new FormData(); fd.append('user_id', otherUserId); return fd; })()
        });
        if (!res.ok) {
            let msg = 'Failed to start conversation';
            try { const err = await res.json(); if (err?.message) msg = err.message; } catch {}
            throw new Error(msg);
        }
        const data = await res.json();
        return data.conversation_id;
    }

    async function loadMessages(conversationId, preserve = false) {
        const url = `{{ url('dashboard/conversations') }}/${conversationId}/messages`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            let msg = 'Failed to load messages';
            try { const err = await res.json(); if (err?.message) msg = err.message; } catch {}
            throw new Error(msg);
        }
        const data = await res.json();
        const items = Array.isArray(data.data) ? data.data : [];
        // Always reset and re-render to avoid duplication on auto-refresh
        renderMessages(items, true);
    }

    async function markRead(conversationId) {
        const url = `{{ url('dashboard/conversations') }}/${conversationId}/messages/read`;
        const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
        if (!res.ok) {
            let msg = 'Failed to mark messages as read';
            try { const err = await res.json(); if (err?.message) msg = err.message; } catch {}
            throw new Error(msg);
        }
        return res.json();
    }

    // Auto-refresh ticks and new messages while a conversation is open
    let messagesRefreshTimer = null;
    function startMessagesAutoRefresh() {
        if (messagesRefreshTimer) {
            clearInterval(messagesRefreshTimer);
            messagesRefreshTimer = null;
        }
        messagesRefreshTimer = setInterval(async () => {
            if (!activeConversationId) return;
            try {
                await loadMessages(activeConversationId, true);
            } catch (e) {
                console.error('Auto refresh failed', e);
            }
        }, 5000);
    }

    async function sendMessage(conversationId, body, files) {
        const url = `{{ url('dashboard/conversations') }}/${conversationId}/messages`;
        const fd = new FormData();
        if (body) fd.append('body', body);
        if (files && files.length) {
            for (const file of files) {
                // Client-side size guard (25MB)
                if (file.size > 25 * 1024 * 1024) {
                    alert(`File ${file.name} exceeds 25MB limit.`);
                    return;
                }
                fd.append('attachments[]', file);
            }
        }
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: fd
        });
        if (!res.ok) {
            let msg = 'Failed to send message';
            try { const err = await res.json(); if (err?.message) msg = err.message; } catch {}
            throw new Error(msg);
        }
        return res.json();
    }

    // Dynamic member search and click-to-open
    const memberListEl = document.getElementById('memberList');
    const memberSearchEl = document.getElementById('memberSearch');
    const memberSearchStatusEl = document.getElementById('memberSearchStatus');

    function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); }; }

    function buildMemberItemHTML(m) {
        const avatar = m.avatar_url || '{{ asset('avatar.jpeg') }}';
        const safeName = (m.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const dot = m.unread_count && m.unread_count > 0 ? '<span class="unread-dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#0d6efd;margin-left:8px;"></span>' : '';
        return `
            <li class="list-group-item d-flex align-items-center member-item" data-user-id="${m.id}" style="cursor: pointer;">
                <img class="rounded-circle me-3" src="${avatar}" alt="avatar" style="width: 32px; height: 32px;">
                <span class="member-name">${safeName}</span>
                ${dot}
            </li>
        `;
    }

    function renderMemberList(members) {
        if (!Array.isArray(members)) return;
        memberListEl.innerHTML = members.map(buildMemberItemHTML).join('');
        // Preserve active highlight if present
        if (activePeerUserId) {
            const activeEl = memberListEl.querySelector(`.member-item[data-user-id="${activePeerUserId}"]`);
            if (activeEl) activeEl.classList.add('active');
        }
    }

    function setSearchStatus(text) {
        memberSearchStatusEl.textContent = text || '';
    }

    async function searchMembers(q) {
        setSearchStatus('Searching…');
        const url = `{{ route('conversations.members.search') }}?q=${encodeURIComponent(q || '')}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            let msg = 'Search failed';
            try { const err = await res.json(); if (err?.message) msg = err.message; } catch {}
            throw new Error(msg);
        }
        const data = await res.json();
        const members = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
        renderMemberList(members);
        setSearchStatus(members.length ? '' : 'No members found');
    }

    const debouncedSearch = debounce(async (q) => {
        try {
            await searchMembers(q);
        } catch (e) {
            console.error(e);
            setSearchStatus(e.message || 'Search failed');
        }
    }, 300);

    memberSearchEl.addEventListener('input', (e) => {
        debouncedSearch(e.target.value.trim());
    });

    // Delegated click: open conversation on result click
    memberListEl.addEventListener('click', async (e) => {
        const li = e.target.closest('.member-item');
        if (!li) return;
        try {
            // Highlight active member
            memberListEl.querySelectorAll('.member-item.active').forEach(el => el.classList.remove('active'));
            li.classList.add('active');
            activePeerUserId = li.dataset.userId;
            activePeerName = li.querySelector('span').textContent.trim();
            document.getElementById('activePeerName').textContent = activePeerName;
            document.getElementById('messages').innerHTML = '<div class="text-muted">Loading…</div>';
            activeConversationId = await ensureConversation(activePeerUserId);
            await loadMessages(activeConversationId);
            await markRead(activeConversationId);
            const dot = li.querySelector('.unread-dot');
            if (dot) dot.remove();
            startMessagesAutoRefresh();
        } catch (e2) {
            console.error(e2);
            alert(e2.message || 'Unable to open conversation.');
        }
    });

    // Composer
    document.getElementById('composer').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            if (!activeConversationId) {
                alert('Select a member first.');
                return;
            }
            const body = document.getElementById('messageBody').value.trim();
            const files = document.getElementById('attachments').files;
            await sendMessage(activeConversationId, body, files);
            document.getElementById('messageBody').value = '';
            document.getElementById('attachments').value = '';
            await loadMessages(activeConversationId);
        } catch (e) {
            console.error(e);
            alert('Failed to send. Please check file types/size.');
        }
    });
</script>
@endsection