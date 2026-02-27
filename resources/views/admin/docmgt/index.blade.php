@extends('dashboards.index')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">Doc Mgt • Members</h5>
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newDocumentModal">
      <i class="fa fa-file-text-o me-1"></i>New Document
    </button>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-12" id="member-col">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa fa-users text-muted"></i>
            <strong>Staff Members</strong>
          </div>
          <div class="w-auto">
            <div class="input-group input-group-sm" id="member-search-group" style="max-width: 280px;">
              <span class="input-group-text"><i class="fa fa-search"></i></span>
              <input type="text" id="member-search" class="form-control" placeholder="Search members" aria-label="Search members" inputmode="search">
              <button class="btn btn-outline-secondary d-none" id="member-search-clear" type="button" aria-label="Clear"><i class="fa fa-times"></i></button>
              <span class="input-group-text d-none" id="member-search-loading" aria-live="polite" aria-busy="true"><i class="fa fa-spinner fa-spin"></i></span>
            </div>
          </div>
        </div>
        <div class="list-group list-group-flush" id="member-list">
          @forelse($users as $user)
            @php
              $detail = $user->userDetail;
              $avatar = optional($detail)->avatar_url ?? optional($detail)->avatar ?? null;
              $lastActivity = optional($user->activities->first())->created_at;
              $isActive = isset($selectedMemberId) && $selectedMemberId === $user->id;
              $act = $memberActivity[$user->id] ?? null;
              $lastInteraction = $act['last_at'] ?? $lastActivity;
              $incoming = $act && ($act['direction'] === 'incoming');
            @endphp
            <a href="{{ route('admin.docmgt', ['member' => $user->id]) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 {{ $isActive ? 'active' : '' }}">
              <img class="rounded-circle flex-shrink-0" src="{{ $avatar ? (Str::startsWith($avatar, ['http://','https://']) ? $avatar : asset('uploads/avatars/' . $avatar)) : asset('avatar.jpeg') }}" alt="avatar" style="width: 40px; height: 40px; object-fit: cover;">
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                  <span class="fw-semibold text-dark d-inline-flex align-items-center gap-2">
                    @if($incoming)
                      <span class="rounded-circle bg-primary" title="Recent document received" style="display:inline-block;width:8px;height:8px"></span>
                    @endif
                    {{ $user->name }}
                  </span>
                  <span class="text-muted small">{{ $lastInteraction ? $lastInteraction->diffForHumans() : '—' }}</span>
                </div>
                <div class="text-muted small">Tap to view document activity</div>
              </div>
            </a>
          @empty
            <div class="p-4 text-center text-muted">No members found for your tenant.</div>
          @endforelse
        </div>
        @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <div class="card-footer d-flex justify-content-center">
            {{ $users->links('pagination::bootstrap-5') }}
          </div>
        @endif
      </div>
    </div>
    <!-- Flyout Panel -->
    <div id="activity-flyout" class="flyout">
      <div class="card shadow-sm h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa fa-file-text-o text-muted"></i>
            <strong id="flyout-title">Document Activity</strong>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="flyout-close" aria-label="Close"><i class="fa fa-times"></i></button>
        </div>
        <div class="card-body p-0">
          <div class="px-3 py-2 border-bottom bg-light">
            <div class="row g-2 align-items-center">
              <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="fa fa-search"></i></span>
                  <input type="text" id="filter-term" class="form-control" placeholder="Search by doc number or title">
                </div>
              </div>
              <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" id="filter-direction" aria-label="Direction filter">
                  <option value="">All directions</option>
                  <option value="Sent">Sent</option>
                  <option value="Received">Received</option>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <input type="date" id="filter-from" class="form-control form-control-sm" placeholder="From">
              </div>
              <div class="col-6 col-md-2">
                <input type="date" id="filter-to" class="form-control form-control-sm" placeholder="To">
              </div>
              <div class="col-6 col-md-2 text-end">
                <button type="button" id="filter-clear" class="btn btn-sm btn-outline-secondary"><i class="fa fa-undo"></i> Clear</button>
              </div>
            </div>
            <div id="filter-badges" class="mt-2 small"></div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="flyout-table">
              <thead class="table-light">
                <tr>
                  <th scope="col">Document No</th>
                  <th scope="col">Title</th>
                  <th scope="col" class="text-nowrap">Date/Time</th>
                  <th scope="col">Direction</th>
                  <th scope="col" class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div id="flyout-backdrop" class="backdrop"></div>
  </div>
</div>
<!-- New Document Modal (Two-step) -->
<div class="modal fade" id="newDocumentModal" tabindex="-1" aria-labelledby="newDocumentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newDocumentModalLabel">New Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @if (session('errors'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <!-- Stepper Header -->
        <div class="d-flex align-items-center mb-3">
          <div class="me-3">
            <span class="badge bg-primary" id="stepBadge1">1</span> <span class="fw-semibold">Select Recipients</span>
          </div>
          <div class="text-muted">→</div>
          <div class="ms-3">
            <span class="badge bg-secondary" id="stepBadge2">2</span> <span class="fw-semibold">Document Details</span>
          </div>
        </div>

        <!-- Step 1: Staff Selection -->
        <div id="step1" role="region" aria-labelledby="stepBadge1">
          <div class="row g-2 align-items-center mb-2">
            <div class="col-12 col-md-6">
              <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" id="recipientSearch" placeholder="Search by name or designation" aria-label="Search by name or designation">
              </div>
            </div>
            <div class="col-12 col-md-6 text-end">
              <small class="text-muted" id="recipientCount">0 selected</small>
            </div>
          </div>
          <div class="border rounded" style="max-height: 320px; overflow-y: auto;">
            <ul class="list-group list-group-flush" id="recipientList">
              @forelse($users as $user)
                @php
                  $detail = $user->userDetail;
                  $avatar = optional($detail)->avatar_url ?? optional($detail)->avatar ?? null;
                  $avatarSrc = $avatar ? (Str::startsWith($avatar, ['http://','https://']) ? $avatar : asset('uploads/avatars/' . $avatar)) : asset('avatar.jpeg');
                @endphp
                <li class="list-group-item d-flex align-items-center justify-content-between recipient-item" data-name="{{ strtolower($user->name) }}" data-designation="{{ strtolower((string) optional($detail)->designation) }}" data-department="{{ strtolower(optional($detail->tenant_department)->name ?? optional($detail->tenant)->name) }}" data-id="{{ $user->id }}">
                  <div class="d-flex align-items-center gap-2">
                    <img src="{{ $avatarSrc }}" alt="avatar" class="rounded-circle" style="width:32px;height:32px;object-fit:cover">
                    <div>
                      <div class="fw-semibold">{{ $user->name }}</div>
                      <small class="text-muted">{{ optional($detail->tenant_department)->name ?? optional($detail->tenant)->name }}</small>
                      <small class="text-muted">{{ optional($detail)->designation }}</small>
                    </div>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input recipient-checkbox" type="checkbox" value="{{ $user->id }}" id="rec_{{ $user->id }}" aria-label="Select {{ $user->name }}">
                  </div>
                </li>
              @empty
                <li class="list-group-item text-center text-muted">No staff found for your tenant.</li>
              @endforelse
              <li class="list-group-item text-center text-muted d-none" id="recipientListEmpty">No recipients found.</li>
            </ul>
          </div>
          <div class="mt-3 d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="step1Next" disabled>Next</button>
          </div>
        </div>

        <!-- Step 2: Document Creation Form -->
        <div id="step2" class="d-none" role="region" aria-labelledby="stepBadge2">
          <form id="newDocForm" action="{{ route('document.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
              <div class="col-12">
                <label for="file_path" class="form-label">Document Upload</label>
                <input type="file" class="form-control" id="file_path" name="file_path" accept="application/pdf" required>
                <div class="form-text">Upload PDF up to 10MB.</div>
              </div>
              <div class="col-12 col-md-6">
                <label for="title" class="form-label">Document Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
              </div>
              <div class="col-12 col-md-6">
                <label for="document_number" class="form-label">Document Number</label>
                <input type="text" class="form-control" id="document_number" name="document_number" value="{{ 'EDMS/' . substr(Str::uuid(), 0, 6) . date('ymd') }}" readonly>
              </div>
              <div class="col-12">
                <label for="description" class="form-label">Document Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
              </div>
              <!-- Optional: include recipients in metadata for later processing -->
              <input type="hidden" name="metadata" id="metadata">
              <!-- Hidden fields to satisfy controller expectations -->
              <input type="hidden" name="uploaded_by" value="{{ Auth::user()->id }}">
              <input type="hidden" name="department_id" value="{{ optional(Auth::user()->userDetail)->department_id }}">
              <input type="hidden" name="tenant_id" value="{{ optional(Auth::user()->userDetail)->tenant_id }}">
            </div>
            <div class="mt-3 d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary" id="step2Back">Back</button>
              <button type="submit" class="btn btn-primary">Create Document</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <style>
    .recipient-item.active { background-color: var(--bs-light); }
  </style>
</div>
<script>
  (function(){
    const listEl = document.getElementById('member-list');
    let selectedMemberId = Number(new URLSearchParams(window.location.search).get('member') || 0);
    const membersEndpointBase = '{{ route('admin.docmgt.activity') }}';
    const activityEndpointBase = '{{ route('admin.docmgt.member_activity') }}';
    const memberCol = document.getElementById('member-col');
    const searchInput = document.getElementById('member-search');
    const searchClearBtn = document.getElementById('member-search-clear');
    const searchLoading = document.getElementById('member-search-loading');
    let currentSearchTerm = '';
    let searchDebounce = null;
    let searchAbort = null;
    let isSearching = false;
    let currentMembers = [];

    const flyout = document.getElementById('activity-flyout');
    const backdrop = document.getElementById('flyout-backdrop');
    const flyoutTableBody = document.querySelector('#flyout-table tbody');
    const flyoutTitle = document.getElementById('flyout-title');
    const flyoutClose = document.getElementById('flyout-close');
    // Filter controls
    const filterTerm = document.getElementById('filter-term');
    const filterDirection = document.getElementById('filter-direction');
    const filterFrom = document.getElementById('filter-from');
    const filterTo = document.getElementById('filter-to');
    const filterClear = document.getElementById('filter-clear');
    const filterBadges = document.getElementById('filter-badges');
    let currentMovements = [];
    let debounceTimer = null;

    // Modal step logic
    const step1El = document.getElementById('step1');
    const step2El = document.getElementById('step2');
    const step1NextBtn = document.getElementById('step1Next');
    const step2BackBtn = document.getElementById('step2Back');
    const recipientSearchInput = document.getElementById('recipientSearch');
    const recipientListEl = document.getElementById('recipientList');
    const recipientCountEl = document.getElementById('recipientCount');
    const metadataEl = document.getElementById('metadata');
    let selectedRecipients = [];
    const recipientsEndpointBase = '{{ route('admin.docmgt.recipients') }}';
    let recipientSearchDebounce = null;
    let recipientSearchAbort = null;
    const defaultAvatar = "{{ asset('avatar.jpeg') }}";

    function updateRecipientCount(){
      recipientCountEl.textContent = selectedRecipients.length + ' selected';
      step1NextBtn.disabled = selectedRecipients.length === 0;
    }

    function toggleRecipient(id, checked){
      const idx = selectedRecipients.indexOf(id);
      if (checked && idx === -1) selectedRecipients.push(id);
      if (!checked && idx !== -1) selectedRecipients.splice(idx, 1);
      // Highlight row
      const row = recipientListEl.querySelector('.recipient-item[data-id="' + id + '"]');
      if (row) row.classList.toggle('active', !!checked);
      updateRecipientCount();
    }

    // Handle direct clicks on checkboxes (more reliable than delegating 'change')
    recipientListEl && recipientListEl.addEventListener('click', function(e){
      const cb = e.target.closest('.recipient-checkbox');
      if (!cb) return;
      const id = Number(cb.value);
      toggleRecipient(id, cb.checked);
      e.stopPropagation();
    });

    // Clicking the row toggles selection unless the checkbox itself was the click target
    recipientListEl && recipientListEl.addEventListener('click', function(e){
      if (e.target.closest('.recipient-checkbox')) return; // avoid double toggles
      const item = e.target.closest('.recipient-item');
      if (!item) return;
      const cb = item.querySelector('.recipient-checkbox');
      if (!cb) return;
      cb.checked = !cb.checked;
      const id = Number(cb.value);
      toggleRecipient(id, cb.checked);
    });

    function relevanceScore(r, term){
      if (!term) return 0;
      const t = term.toLowerCase();
      const name = String(r.name || '').toLowerCase();
      const desig = String(r.designation || '').toLowerCase();
      const dept = String(r.department || '').toLowerCase();
      let score = 0;
      if (name.startsWith(t)) score += 100; else if (name.includes(t)) score += 70;
      if (desig.startsWith(t)) score += 60; else if (desig.includes(t)) score += 40;
      if (dept.startsWith(t)) score += 30; else if (dept.includes(t)) score += 20;
      return score;
    }

    function renderRecipients(items, term){
      // Sort by relevance when searching; fall back to name ASC otherwise
      const sorted = (items || []).slice();
      if (term && term.trim().length) {
        sorted.sort(function(a, b){
          const sa = relevanceScore(a, term);
          const sb = relevanceScore(b, term);
          if (sb !== sa) return sb - sa;
          return String(a.name || '').localeCompare(String(b.name || ''));
        });
      } else {
        sorted.sort(function(a, b){
          return String(a.name || '').localeCompare(String(b.name || ''));
        });
      }

      const rows = sorted.map(function(r){
        const isChecked = selectedRecipients.includes(Number(r.id));
        return (
          '<li class="list-group-item d-flex align-items-center justify-content-between recipient-item"' +
            ' data-name="' + (String(r.name || '').toLowerCase()) + '"' +
            ' data-designation="' + (String(r.designation || '').toLowerCase()) + '"' +
            ' data-department="' + (String(r.department || '').toLowerCase()) + '"' +
            ' data-id="' + r.id + '">' +
            '<div class="d-flex align-items-center gap-2">' +
              '<img src="' + (r.avatar || defaultAvatar) + '" alt="avatar" class="rounded-circle" style="width:32px;height:32px;object-fit:cover">' +
              '<div>' +
                '<div class="fw-semibold">' + (r.name || '') + '</div>' +
                '<small class="text-muted">' + (r.department || '') + '</small>' +
                '<small class="text-muted">' + (r.designation || '') + '</small>' +
              '</div>' +
            '</div>' +
            '<div class="form-check">' +
              '<input class="form-check-input recipient-checkbox" type="checkbox" value="' + r.id + '" id="rec_' + r.id + '"' + (isChecked ? ' checked' : '') + ' aria-label="Select ' + (r.name || '') + '">' +
            '</div>' +
          '</li>'
        );
      }).join('');

      const hasTerm = !!(term && term.trim().length);
      const emptyMsg = hasTerm ? 'Recipient not found.' : 'No recipients found.';
      const emptyRow = '<li class="list-group-item text-center text-muted ' + (sorted.length ? 'd-none' : '') + '" id="recipientListEmpty">' + emptyMsg + '</li>';
      recipientListEl.innerHTML = rows + emptyRow;

      // Re-apply active highlighting for selected recipients
      selectedRecipients.forEach(function(id){
        const row = recipientListEl.querySelector('.recipient-item[data-id="' + id + '"]');
        if (row) row.classList.add('active');
      });

      updateRecipientCount();
    }

    async function fetchRecipients(term){
      const params = new URLSearchParams();
      if (term) params.set('q', term);
      const endpoint = recipientsEndpointBase + (params.toString() ? ('?' + params.toString()) : '');
      try {
        if (recipientSearchAbort) { try { recipientSearchAbort.abort(); } catch(_){} }
        recipientSearchAbort = new AbortController();
        const res = await fetch(endpoint, { headers: { 'Accept': 'application/json' }, signal: recipientSearchAbort.signal });
        if (!res.ok) return;
        const data = await res.json();
        const items = data.recipients || [];
        renderRecipients(items, term);
      } catch(e) {
        // Fallback to local sort/filter on error for smooth UX
        localSortRecipients(term);
      }
    }

    function handleRecipientSearch(){
      const term = (recipientSearchInput.value || '').trim();
      // Immediate local reorder/filter for responsiveness
      localSortRecipients(term);
      if (recipientSearchDebounce) clearTimeout(recipientSearchDebounce);
      recipientSearchDebounce = setTimeout(function(){
        fetchRecipients(term);
      }, 300);
    }

    function computeNodeScore(li, term){
      if (!term) return 0;
      const t = term.toLowerCase();
      const name = (li.getAttribute('data-name') || '').toLowerCase();
      const desig = (li.getAttribute('data-designation') || '').toLowerCase();
      const dept = (li.getAttribute('data-department') || '').toLowerCase();
      let score = 0;
      if (name.startsWith(t)) score += 100; else if (name.includes(t)) score += 70;
      if (desig.startsWith(t)) score += 60; else if (desig.includes(t)) score += 40;
      if (dept.startsWith(t)) score += 30; else if (dept.includes(t)) score += 20;
      return score;
    }

    function localSortRecipients(term){
      const items = Array.from(recipientListEl.querySelectorAll('.recipient-item'));
      const hasTerm = !!(term && term.trim().length);
      if (!hasTerm) {
        // When no term, keep current ordering (alphabetical from server)
        // Also ensure empty-state hidden if there are items
        const emptyEl = document.getElementById('recipientListEmpty');
        if (emptyEl) emptyEl.classList.add('d-none');
        return;
      }
      const matched = items.filter(function(li){
        const name = (li.getAttribute('data-name') || '').toLowerCase();
        const desig = (li.getAttribute('data-designation') || '').toLowerCase();
        const dept = (li.getAttribute('data-department') || '').toLowerCase();
        const text = (li.textContent || '').toLowerCase();
        const t = term.toLowerCase();
        return name.includes(t) || desig.includes(t) || dept.includes(t) || text.includes(t);
      });
      matched.sort(function(a, b){
        const sa = computeNodeScore(a, term);
        const sb = computeNodeScore(b, term);
        if (sb !== sa) return sb - sa;
        const na = (a.getAttribute('data-name') || '');
        const nb = (b.getAttribute('data-name') || '');
        return na.localeCompare(nb);
      });

      // Clear list and append matched in order
      const emptyElExisting = document.getElementById('recipientListEmpty');
      recipientListEl.innerHTML = '';
      matched.forEach(function(li){ recipientListEl.appendChild(li); });
      // Append empty-state row appropriately
      const emptyMsg = 'Recipient not found.';
      const emptyRow = document.createElement('li');
      emptyRow.id = 'recipientListEmpty';
      emptyRow.className = 'list-group-item text-center text-muted ' + (matched.length ? 'd-none' : '');
      emptyRow.textContent = emptyMsg;
      recipientListEl.appendChild(emptyRow);
    }

    recipientSearchInput && recipientSearchInput.addEventListener('input', handleRecipientSearch, { passive: true });

    step1NextBtn && step1NextBtn.addEventListener('click', function(){
      // Move to step 2 and embed recipients in metadata
      const meta = { recipients: selectedRecipients };
      metadataEl && (metadataEl.value = JSON.stringify(meta));
      step1El.classList.add('d-none');
      step2El.classList.remove('d-none');
      document.getElementById('stepBadge1').classList.remove('bg-primary');
      document.getElementById('stepBadge1').classList.add('bg-secondary');
      document.getElementById('stepBadge2').classList.remove('bg-secondary');
      document.getElementById('stepBadge2').classList.add('bg-primary');
    });

    step2BackBtn && step2BackBtn.addEventListener('click', function(){
      step2El.classList.add('d-none');
      step1El.classList.remove('d-none');
      document.getElementById('stepBadge1').classList.add('bg-primary');
      document.getElementById('stepBadge1').classList.remove('bg-secondary');
      document.getElementById('stepBadge2').classList.add('bg-secondary');
      document.getElementById('stepBadge2').classList.remove('bg-primary');
    });

    function renderMembers(members){
      const rows = members.map(function(m){
        const dot = m.incoming ? '<span class="rounded-circle bg-primary" title="Recent document received" style="display:inline-block;width:8px;height:8px"></span>' : '';
        const activeCls = m.active ? 'active' : '';
        return (
          '<a href="' + m.href + '" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 ' + activeCls + '">' +
            '<img class="rounded-circle flex-shrink-0" src="' + m.avatar + '" alt="avatar" style="width:40px;height:40px;object-fit:cover;" />' +
            '<div class="flex-grow-1">' +
              '<div class="d-flex justify-content-between">' +
                '<span class="fw-semibold text-dark d-inline-flex align-items-center gap-2">' + dot + ' ' + m.name + '</span>' +
                '<span class="text-muted small">' + (m.last_human || '—') + '</span>' +
              '</div>' +
              '<div class="text-muted small">Tap to view document activity</div>' +
            '</div>' +
          '</a>'
        );
      }).join('');
      listEl.innerHTML = rows || '<div class="p-4 text-center text-muted">' + (isSearching ? 'No members found' : 'No members found for your tenant.') + '</div>';
    }

    function getFilteredMembers(){
      const term = (currentSearchTerm || '').trim().toLowerCase();
      let base = currentMembers.slice();
      if (term.length >= 2) {
        base = base.filter(function(m){
          const name = (m.name || '').toLowerCase();
          return name.includes(term);
        });
      }
      // Preserve sorting by recent activity
      base.sort(function(a, b){
        const ta = a.last_at ? Date.parse(a.last_at) : 0;
        const tb = b.last_at ? Date.parse(b.last_at) : 0;
        return tb - ta;
      });
      return base;
    }

    async function refreshMembers(){
      const params = new URLSearchParams();
      if (selectedMemberId) params.set('member', selectedMemberId);
      if (currentSearchTerm && currentSearchTerm.length >= 2) params.set('q', currentSearchTerm);
      const endpoint = membersEndpointBase + (params.toString() ? ('?' + params.toString()) : '');
      try {
        if (searchAbort) { try { searchAbort.abort(); } catch(_){} }
        searchAbort = new AbortController();
        const res = await fetch(endpoint, { headers: { 'Accept': 'application/json' }, signal: searchAbort.signal });
        if (!res.ok) return;
        const data = await res.json();
        currentMembers = data.members || [];
        renderMembers(getFilteredMembers());
      } catch(e) {
        // Silently ignore errors to avoid user disruption
      } finally {
        searchLoading && searchLoading.classList.add('d-none');
      }
    }

    // Initial load and polling every 10s (respects current search term)
    refreshMembers();
    setInterval(refreshMembers, 10000);

    async function fetchMemberActivity(memberId){
      try {
        const res = await fetch(activityEndpointBase + '?member=' + memberId, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return [];
        const data = await res.json();
        return data.movements || [];
      } catch(e) { return []; }
    }

    function renderActivityRows(items){
      const rows = items.map(function(it){
        const badge = it.direction === 'Sent' ? 'bg-primary' : 'bg-success';
        const dt = it.updated_human || '—';
        return (
          '<tr>' +
            '<td class="text-nowrap"><a href="' + it.view_url + '" class="text-decoration-none">' + (it.doc_no || '—') + '</a></td>' +
            '<td><a href="' + it.view_url + '" class="text-decoration-none">' + (it.title || 'Untitled') + '</a></td>' +
            '<td class="text-nowrap">' + dt + '</td>' +
            '<td><span class="badge ' + badge + '">' + it.direction + '</span></td>' +
            '<td class="text-end"><a href="' + it.view_url + '" class="btn btn-sm btn-outline-secondary"><i class="fa fa-eye"></i></a></td>' +
          '</tr>'
        );
      }).join('');
      flyoutTableBody.innerHTML = rows || '<tr><td colspan="5" class="text-center text-muted py-4">No document activity.</td></tr>';
    }

    function formatDateOnly(iso){
      if (!iso) return null;
      try { return new Date(iso).toISOString().slice(0,10); } catch(e){ return null; }
    }

    function applyFilters(){
      let term = (filterTerm.value || '').trim().toLowerCase();
      const dir = filterDirection.value || '';
      const from = filterFrom.value || '';
      const to = filterTo.value || '';

      let filtered = currentMovements.slice();
      if (term) {
        filtered = filtered.filter(function(it){
          const dn = (it.doc_no || '').toLowerCase();
          const tt = (it.title || '').toLowerCase();
          return dn.includes(term) || tt.includes(term);
        });
      }
      if (dir) {
        filtered = filtered.filter(function(it){ return it.direction === dir; });
      }
      if (from) {
        filtered = filtered.filter(function(it){
          const d = formatDateOnly(it.updated_at);
          return d && d >= from;
        });
      }
      if (to) {
        filtered = filtered.filter(function(it){
          const d = formatDateOnly(it.updated_at);
          return d && d <= to;
        });
      }

      // Maintain existing sorting (server returns most recent first)
      renderActivityRows(filtered);

      // Visual feedback badges
      const badges = [];
      if (term) badges.push('<span class="badge bg-secondary me-1">"' + term + '"</span>');
      if (dir) badges.push('<span class="badge ' + (dir === 'Sent' ? 'bg-primary' : 'bg-success') + ' me-1">' + dir + '</span>');
      if (from) badges.push('<span class="badge bg-light text-dark border me-1">From: ' + from + '</span>');
      if (to) badges.push('<span class="badge bg-light text-dark border me-1">To: ' + to + '</span>');
      const count = '<span class="ms-2 text-muted">' + filtered.length + ' result' + (filtered.length === 1 ? '' : 's') + '</span>';
      filterBadges.innerHTML = (badges.length ? badges.join('') : '') + count;
    }

    function triggerDebouncedFilter(){
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(applyFilters, 300);
    }

    function openFlyout(member){
      selectedMemberId = Number(member.id || member);
      // Update URL to preserve selection
      const url = new URL(window.location.href);
      url.searchParams.set('member', selectedMemberId);
      window.history.replaceState({}, '', url.toString());

      // Compress left panel (desktop)
      memberCol.classList.remove('col-lg-12');
      memberCol.classList.add('col-lg-5');

      // Show backdrop and flyout
      backdrop.classList.add('show');
      flyout.classList.add('open');

      // Load movements
      fetchMemberActivity(selectedMemberId).then(function(items){
        flyoutTitle.textContent = 'Document Activity';
        currentMovements = items || [];
        // Reset filters on new open
        filterTerm.value = '';
        filterDirection.value = '';
        filterFrom.value = '';
        filterTo.value = '';
        filterBadges.innerHTML = '';
        applyFilters();
      });
    }

    function closeFlyout(){
      // Remove URL param
      const url = new URL(window.location.href);
      url.searchParams.delete('member');
      window.history.replaceState({}, '', url.toString());

      // Reset left panel width
      memberCol.classList.remove('col-lg-5');
      memberCol.classList.add('col-lg-12');

      backdrop.classList.remove('show');
      flyout.classList.remove('open');
    }

    // Delegate clicks on member list to open flyout without full page reload
    listEl.addEventListener('click', function(e){
      const a = e.target.closest('a.list-group-item');
      if (!a) return;
      e.preventDefault();
      const params = new URL(a.href).searchParams;
      const id = Number(params.get('member'));
      openFlyout({ id });
    });

    flyoutClose.addEventListener('click', function(){ closeFlyout(); });
    backdrop.addEventListener('click', function(){ closeFlyout(); });

    // If page loads with a selected member, open flyout immediately
    if (selectedMemberId) {
      openFlyout({ id: selectedMemberId });
    }

    // Search: debounced input, min 2 chars, clear button, loading spinner
    function handleSearchInput(){
      const term = (searchInput.value || '').trim();
      currentSearchTerm = term;
      isSearching = term.length >= 2;
      // Toggle clear button visibility
      if (term.length) {
        searchClearBtn.classList.remove('d-none');
      } else {
        searchClearBtn.classList.add('d-none');
      }
      // Immediate client-side filter for responsiveness
      renderMembers(getFilteredMembers());
      // Debounced fetch
      if (searchDebounce) clearTimeout(searchDebounce);
      searchDebounce = setTimeout(function(){
        // Show loading only when actually querying
        if (isSearching) {
          searchLoading.classList.remove('d-none');
        } else {
          searchLoading.classList.add('d-none');
        }
        refreshMembers();
      }, 300);
    }

    searchInput && searchInput.addEventListener('input', handleSearchInput, { passive: true });
    searchClearBtn && searchClearBtn.addEventListener('click', function(){
      searchInput.value = '';
      currentSearchTerm = '';
      isSearching = false;
      searchClearBtn.classList.add('d-none');
      searchLoading.classList.add('d-none');
      refreshMembers();
      // Return focus for quick re-entry on mobile
      searchInput.focus();
    });

    // Filter listeners
    filterTerm && filterTerm.addEventListener('input', triggerDebouncedFilter);
    filterDirection && filterDirection.addEventListener('change', applyFilters);
    filterFrom && filterFrom.addEventListener('input', triggerDebouncedFilter);
    filterTo && filterTo.addEventListener('input', triggerDebouncedFilter);
    filterClear && filterClear.addEventListener('click', function(){
      filterTerm.value = '';
      filterDirection.value = '';
      filterFrom.value = '';
      filterTo.value = '';
      applyFilters();
    });
  })();
</script>
<style>
  /* Flyout panel and backdrop */
  .flyout {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 70vw; /* ~70% width on desktop */
    max-width: 900px;
    background: var(--bs-body-bg);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    z-index: 1040;
    padding: 1rem;
  }
  .flyout.open { transform: translateX(0); }
  .backdrop {
    position: fixed; inset: 0; background: rgba(0,0,0,0.35);
    opacity: 0; pointer-events: none; transition: opacity 0.2s ease; z-index: 1030;
  }
  .backdrop.show { opacity: 1; pointer-events: auto; }
  /* Mobile: full-screen overlay */
  @media (max-width: 992px) {
    .flyout { width: 100vw; max-width: none; padding: 0.5rem; }
  }
</style>
@endsection
