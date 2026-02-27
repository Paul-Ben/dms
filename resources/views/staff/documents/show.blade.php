
@extends('dashboards.index')
@section('content')
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f6f8fc;
            padding: 15px;
        }

        .email-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            padding: 15px;
        }

        .toolbar {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            background: white;
            color: #444;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .btn:hover {
            background: #f1f3f4;
        }

        .btn svg {
            width: 14px;
            height: 14px;
        }

        .email-header {
            margin-bottom: 15px;
        }

        .subject {
            font-size: 18px;
            color: #202124;
            margin-bottom: 12px;
        }

        .email-meta {
            display: grid;
            grid-template-columns: 70px 1fr;
            gap: 8px;
            font-size: 13px;
            color: #5f6368;
        }

        .email-body {
            color: #202124;
            line-height: 1.5;
            margin: 15px 0;
            font-size: 14px;
        }

        .attachment {
            border: 1px solid #dadce0;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .attachment:hover {
            background: #f8f9fa;
        }

        .file-icon {
            width: 32px;
            height: 32px;
            background: #f1f3f4;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            color: #202124;
            font-size: 14px;
        }

        .file-meta {
            font-size: 11px;
            color: #5f6368;
        }

        .preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .preview-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 15px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow: auto;
        }

        .close-preview {
            position: absolute;
            right: 15px;
            top: 15px;
            cursor: pointer;
        }

        /* Timeline Styling */
        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            height: 100%;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 12px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid white;
        }

        /* Message Bubble */
        .message-bubble {
            position: relative;
            border-left: 3px solid #0d6efd;
            padding: 12px;
            background: white;
            border-radius: 0 8px 8px 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Avatar */
        .avatar {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        @media (min-width: 768px) {
            body {
                padding: 20px;
            }
            
            .email-container {
                padding: 20px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }
            
            .btn svg {
                width: 16px;
                height: 16px;
            }
            
            .subject {
                font-size: 20px;
            }
            
            .email-meta {
                grid-template-columns: 80px 1fr;
                font-size: 14px;
            }
            
            .timeline {
                padding-left: 50px;
            }
            
            .timeline-item::before {
                left: -40px;
                width: 12px;
                height: 12px;
            }
            
            .avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        /* STT Microphone Button */
        .stt-mic-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.2s;
            font-size: 1.2rem;
            z-index: 5;
        }
        .stt-mic-btn:hover { color: #0d6efd; }
        .stt-mic-btn.listening {
            color: #dc3545;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
    @include('partials.document_split_layout_css')

    <div class="container-fluid pt-3 pt-md-4 px-2 px-md-3">
        <div class="bg-light rounded p-3 p-md-4">
            <div class="email-container">
                <div class="toolbar">
                    <a href="{{ route('document.reply', $document_received->document_id) }}" class="text-decoration-none">
                        <button class="btn">
                            <svg viewBox="0 0 24 24">
                                <path fill="currentColor" d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" />
                            </svg>
                            <span class="d-none d-sm-inline">Reply the mail</span>
                            <span class="d-inline d-sm-none">Reply</span>
                        </button>
                    </a>
                    <a href="{{ route('document.send', $document_received->document_id) }}" class="text-decoration-none">
                        <button class="btn">
                            <svg viewBox="0 0 24 24">
                                <path fill="currentColor" d="M14 9v-4l7 7-7 7v-4.1c-5 0-8.5 1.6-11 5.1 1-5 4-10 11-11z" />
                            </svg>
                            <span class="d-none d-sm-inline">Minute the mail</span>
                            <span class="d-inline d-sm-none">Minute</span>
                        </button>
                    </a>
                    <a href="#priviousmiuting">
                        <button class="btn" type="button">
                            <svg viewBox="0 0 24 24">
                                <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                            </svg>
                            <span class="d-none d-md-inline">Previous Minuting</span>
                            <span class="d-inline d-md-none">History</span>
                        </button>
                    </a>

                    <a href="{{ route('track', $document_received->document_id) }}" class="text-decoration-none">
                        <button class="btn">
                            <svg viewBox="0 0 24 24">
                                <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                            </svg>
                            Track
                        </button>
                    </a>
                    @if ($document_received->attachments->isNotEmpty())
                        <a href="{{ route('getAttachments', $document_received->document_id) }}" class="text-decoration-none">
                            <button class="btn">
                                <svg viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                                </svg>
                                <span class="d-none d-sm-inline">Attachments</span>
                                <span class="d-inline d-sm-none">Files</span>
                            </button>
                        </a>
                    @endif
                    <a href="{{ route('folders.select', $document_received->document->id) }}" 
                        class="text-decoration-none"
                        title="Add to folder">
                         <button class="btn">
                            <i class="fas fa-folder-plus"></i>
                            Add to Folder
                         </button>
                     </a>
                    <a href="{{ url()->previous() }}" class="text-decoration-none">
                        <button class="btn">
                            <svg viewBox="0 0 24 24">
                                <path fill="currentColor" d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z" />
                            </svg>
                            Back
                        </button>
                    </a>
                </div>

                <!-- Split Layout -->
                <div class="doc-split">
                    <!-- Left Panel: Document Content -->
                    <div class="doc-panel panel-left">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-file-alt me-2"></i> Current Document
                            </h5>
                            <span class="badge bg-primary text-white">
                                {{ $document_received->document->docuent_number }}
                            </span>
                        </div>
                        
                        <div class="flex-grow-1 bg-light position-relative" style="min-height: 500px;">
                            @php
                                $fileUrl = $document_received->document->file_path;
                                if (!preg_match('/^https?:\/\//', $fileUrl)) {
                                    $fileUrl = asset('storage/' . $fileUrl);
                                }
                            @endphp
                            <iframe src="{{ $fileUrl }}" class="w-100 h-100 border-0" style="min-height: 600px;"></iframe>
                        </div>

                        <!-- Sticky Actions Footer -->
                        <div class="sticky-actions">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex gap-2">
                                    {{-- <a href="{{ $document_received->document->file_path }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a> --}}
                                    @if ($document_received->attachments->isNotEmpty())
                                        <a href="{{ $document_received->attachments[0]->attachment }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-paperclip mr-1"></i> Attachment
                                        </a>
                                    @endif
                                    <button id="shareBtn"
                                            class="btn btn-sm btn-outline-secondary ms-1"
                                            type="button"
                                            data-title="{{ $document_received->document->title }}"
                                            data-number="{{ $document_received->document->docuent_number }}">
                                        <i class="fa fa-share-alt me-1"></i> Minute and Send
                                    </button>
                                </div>
                                <small class="text-muted">
                                    Received: {{ $document_received->created_at->format('M j, Y') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Resizer -->
                    <div class="resizer" id="dragMe"></div>

                    <!-- Right Panel: History/Minuting -->
                    <div class="doc-panel panel-right">
                        <div class="p-3 border-bottom bg-white sticky-top">
                            <h5 class="mb-0 fw-bold text-primary" id="priviousmiuting">
                                <i class="fas fa-history me-2"></i> History
                            </h5>
                        </div>
                        
                        <div class="flex-grow-1 overflow-auto p-3" style="max-height: 800px;">
                            <!-- Document Info Card -->
                            <div class="card mb-3 border-0 shadow-sm bg-light">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-1 text-dark">
                                        {{ $document_received->document->title }}
                                    </h6>
                                    <div class="text-muted small">
                                        #{{ $document_received->document->docuent_number }}
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div class="timeline">
                                @foreach ($document_locations as $location)
                                    <div class="timeline-item">
                                        <!-- Header -->
                                        <div class="message-header d-flex justify-content-between align-items-start mb-2" title="click to view minuting" data-toggle="tooltip"
                                             onclick="this.parentNode.querySelector('.message-content').classList.toggle('d-none')"
                                             style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-primary text-white rounded-circle mr-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                                    {{ strtoupper(substr($location->sender->name, 0, 1)) }}
                                                </div>
                                                <div style="line-height: 1.2;">
                                                    <h6 class="mb-0 fw-bold small text-dark">{{ $location->sender->name }}</h6>
                                                    <small class="text-muted" style="font-size: 11px;">
                                                        {{ $location->sender->userDetail->designation }}
                                                    </small>
                                                </div>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">
                                                {{ $location->created_at->format('M j') }}
                                            </small>
                                        </div>

                                        <!-- Content -->
                                        <div class="message-content d-none animate__animated animate__fadeIn">
                                            <div class="message-bubble bg-white border p-3 rounded shadow-sm">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <small class="text-muted">To: {{ $location->recipient->name }}</small>
                                                </div>
                                                <p class="mb-0 small text-dark">{{ $location->message }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minuting Section: Appears after recipients are selected -->
                <div id="minutingSection" class="card mt-4 d-none">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-comment-dots text-primary"></i>
                            <strong>Minuting</strong>
                        </div>
                        <small class="text-muted">Compose a note to accompany the document</small>
                    </div>
                    <div class="card-body">
                        <div id="shareFeedback" class="alert d-none" role="alert"></div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Recipients</label>
                            <div id="minutingRecipients" class="d-flex flex-wrap gap-2"></div>
                        </div>
                        <div class="mb-2">
                            <label for="minutingMessage" class="form-label fw-bold">Message</label>
                            <div class="stt-wrapper position-relative">
                                <textarea id="minutingMessage" class="form-control" rows="4" placeholder="Type your minute/message here..."></textarea>
                                <button type="button" id="sttToggle" class="stt-mic-btn" aria-label="Dictate message" title="Click to speak">
                                    <i class="fas fa-microphone"></i>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small id="sttStatus" class="text-muted" aria-live="polite">Click to speak</small>
                                <small class="text-muted">Characters: <span id="minutingCharCount">0</span></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" id="minutingSendBtn" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send / Share
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="forwardedMessageModal" tabindex="-1" role="dialog"
        aria-labelledby="forwardedMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forwardedMessageModalLabel">
                        Previous Minutes
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @foreach ($document_locations as $location)
                        <div class="forwarded-content mb-4">
                            <p class="text-center font-weight-bold">----------
                                {{ $document_received->document->docuent_number }} ----------</p>
                            <p class="mb-1">
                                <strong>From:</strong> {{ $location->sender->name }}
                                &lt;{{ $location->sender->userDetail->designation }}&gt;
                            </p>
                            <p class="mb-1">
                                <strong>Date:</strong> {{ $location->updated_at->format('M j, Y g:i A') }}
                            </p>
                            <p class="mb-1">
                                <strong>Subject:</strong> {{ $document_received->document->title }}
                            </p>
                            <p class="mb-3">
                                <strong>To:</strong> {{ $location->recipient->name }}
                                &lt;{{ $location->recipient->userDetail->designation }}&gt;
                            </p>
                            <p>Hi {{ $location->recipient->name }},</p>
                            <p class="mb-3">
                                {{ $location->message }}
                            </p>
                            <p>Best regards,</p>
                            <p>{{ $location->sender->name }}</p>
                        </div>
                        @if (!$loop->last)
                            <hr class="my-4">
                        @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal: Tenant Members selector -->
    <div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <input id="shareMemberSearch" type="text" class="form-control" placeholder="Search members by name" aria-label="Search members">
                        <small id="shareSearchStatus" class="text-muted ms-2"></small>
                    </div>
                    <div class="border rounded" style="max-height: 50vh; overflow:auto;">
                        <ul id="shareMemberList" class="list-group list-group-flush"></ul>
                    </div>
                </div>
                <div class="modal-footer share-footer d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" id="shareProceedBtn" class="btn btn-primary">Next</button>
                        <small class="text-muted">Selected: <span id="shareSelectedCount">0</span></small>
                    </div>
                    <button type="button" id="shareFooterCloseBtn" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Share modal list styling */
        #shareMemberList .list-group-item {
            cursor: pointer;
        }
        #shareMemberList .list-group-item.active,
        #shareMemberList .list-group-item.selected {
            background-color: #e9f3ff;
        }
        .share-member-avatar {
            width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
        }
        @media (max-width: 576px) {
            #shareMemberList { max-height: 60vh; }
        }

        /* Minuting recipients chips */
        #minutingRecipients .recipient-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            background: #f8f9fa;
            margin: 4px 6px 0 0;
            font-size: 13px;
        }
        #minutingRecipients .recipient-chip img {
            width: 20px; height: 20px; border-radius: 50%; object-fit: cover;
        }
        #minutingRecipients .recipient-chip .name { font-weight: 500; color: #202124; }
        #minutingRecipients .recipient-chip .email { color: #6c757d; }
        @media (max-width: 576px) {
            #minutingRecipients .recipient-chip { font-size: 12px; padding: 5px 8px; }
        }

        /* Share modal footer layout and responsiveness */
        #shareModal .share-footer {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #dee2e6;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
            #shareModal .btn-primary {
                background-color: #0d6efd;
                border-color: #0d6efd;
            }
        @media (max-width: 576px) {
            #shareModal .share-footer { flex-direction: column; align-items: stretch; gap: 0.5rem; }
            #shareModal .share-footer .d-flex { width: 100%; justify-content: space-between; }
            #shareFooterCloseBtn { width: 100%; }
        }

        /* Minuting Send/Share button: blue primary with hover/focus accessibility */
        #minutingSendBtn.btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
            min-height: 44px; /* touch-friendly size */
        }
        #minutingSendBtn.btn-primary:hover {
            background-color: #0b5ed7; /* lighter/darker variant for hover */
            border-color: #0a58ca;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2);
        }

        /* Speech-to-Text mic button positioning and states */
        .stt-wrapper { position: relative; }
        .stt-mic-btn {
            position: absolute;
            right: 10px;
            bottom: 10px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #dee2e6;
            color: #0d6efd;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            z-index: 2;
        }
        .stt-mic-btn:hover { box-shadow: 0 2px 6px rgba(13,110,253,0.2); }
        .stt-mic-btn:focus { outline: 2px solid rgba(13,110,253,0.25); outline-offset: 2px; }
        .stt-mic-btn.listening { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .stt-mic-btn.listening .fa-microphone { animation: sttPulse 1.2s infinite; }
        @keyframes sttPulse { 0% { transform: scale(1); } 50% { transform: scale(1.15); } 100% { transform: scale(1); } }
        @media (max-width: 576px) { .stt-mic-btn { right: 8px; bottom: 8px; } }
        #minutingSendBtn.btn-primary:focus {
            outline: 2px solid rgba(13, 110, 253, 0.25);
            outline-offset: 2px;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        #minutingSendBtn.btn-primary:active {
            background-color: #0a58ca;
            border-color: #0a53be;
        }
    </style>

    <script>
        // Split layout resizer
        (function() {
            const resizer = document.getElementById('dragMe');
            const left = document.querySelector('.panel-left');
            const right = document.querySelector('.panel-right');
            const split = document.querySelector('.doc-split');
            if (!resizer || !left || !right || !split) return;

            let isDragging = false;
            let startX = 0;
            let startLeftWidth = 0;

            function onMouseDown(e) {
                if (window.matchMedia('(max-width: 992px)').matches) return;
                isDragging = true;
                startX = e.clientX;
                startLeftWidth = left.getBoundingClientRect().width;
                document.body.style.cursor = 'col-resize';
                document.body.classList.add('user-select-none');
            }

            function onMouseMove(e) {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const newLeftWidth = startLeftWidth + dx;
                const totalWidth = split.getBoundingClientRect().width;

                if (newLeftWidth > 300 && newLeftWidth < totalWidth - 300) {
                    const percentage = (newLeftWidth / totalWidth) * 100;
                    left.style.flex = `0 0 ${percentage}%`;
                }
            }

            function onMouseUp() {
                isDragging = false;
                document.body.style.cursor = '';
                document.body.classList.remove('user-select-none');
            }

            resizer.addEventListener('mousedown', onMouseDown);
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        })();

        // Share button handler: open members selection modal
        (function() {
            const shareBtn = document.getElementById('shareBtn');
            const listEl = document.getElementById('shareMemberList');
            const searchEl = document.getElementById('shareMemberSearch');
            const statusEl = document.getElementById('shareSearchStatus');
            const selectedCountEl = document.getElementById('shareSelectedCount');
            const proceedBtn = document.getElementById('shareProceedBtn');
            const headerCloseBtn = document.querySelector('#shareModal .modal-header .close');
            const footerCloseBtn = document.getElementById('shareFooterCloseBtn');
            if (!shareBtn || !listEl || !searchEl || !statusEl || !selectedCountEl || !proceedBtn) return;

            const selectedIds = new Set();
            const memberCache = new Map(); // id -> { id, name, avatar_url, email? }

            // Minuting section elements
            const minutingSection = document.getElementById('minutingSection');
            const recipientsEl = document.getElementById('minutingRecipients');
            const msgEl = document.getElementById('minutingMessage');
            const msgCountEl = document.getElementById('minutingCharCount');
            const sendBtn = document.getElementById('minutingSendBtn');
            const feedbackEl = document.getElementById('shareFeedback');

            // Backend submission config
            const sendDocUrl = `{{ route('document.senddoc', $document_received->document) }}`;
            const csrfToken = `{{ csrf_token() }}`;

            function renderRecipientsIndicator() {
                const ids = Array.from(selectedIds);
                if (!ids.length) {
                    if (minutingSection) minutingSection.classList.add('d-none');
                    recipientsEl.innerHTML = '';
                    return;
                }
                if (minutingSection) minutingSection.classList.remove('d-none');
                const chips = ids.map((id) => {
                    const m = memberCache.get(id) || { id, name: `User #${id}`, avatar_url: '{{ asset('avatar.jpeg') }}' };
                    const safeName = (m.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const email = m.email ? `<span class="email">${m.email}</span>` : '';
                    return `
                        <span class="recipient-chip" data-user-id="${id}">
                            <img src="${m.avatar_url || '{{ asset('avatar.jpeg') }}'}" alt="avatar">
                            <span class="name">${safeName}</span>
                            ${email}
                        </span>
                    `;
                });
                recipientsEl.innerHTML = chips.join('');
            }

            function updateSelectedCount() { selectedCountEl.textContent = String(selectedIds.size); }
            function updateMinuting() { updateSelectedCount(); renderRecipientsIndicator(); }

            function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); }; }

            function setStatus(text) { statusEl.textContent = text || ''; }

            function buildItem(m) {
                const safeName = (m.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const checked = selectedIds.has(m.id) ? 'checked' : '';
                const activeClass = selectedIds.has(m.id) ? 'selected' : '';
                return `
                    <li class="list-group-item d-flex align-items-center ${activeClass}" data-user-id="${m.id}">
                        <input type="checkbox" class="form-check-input me-2" ${checked} aria-label="Select ${safeName}">
                        <img src="${m.avatar_url || '{{ asset('avatar.jpeg') }}' }" alt="avatar" class="share-member-avatar me-2">
                        <span class="flex-grow-1">${safeName}</span>
                    </li>
                `;
            }

            function renderList(members) {
                listEl.innerHTML = (Array.isArray(members) ? members : []).map(buildItem).join('');
                // Update cache for recipients indicator
                (Array.isArray(members) ? members : []).forEach(m => { memberCache.set(m.id, m); });
            }

            async function searchMembers(q) {
                setStatus('Searching…');
                const url = `{{ route('conversations.members.search') }}?q=${encodeURIComponent(q || '')}`;
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('Search failed');
                    const data = await res.json();
                    const members = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
                    renderList(members);
                    setStatus(members.length ? '' : 'No members found');
                } catch (e) {
                    console.error(e);
                    setStatus(e.message || 'Search failed');
                }
            }

            const debounced = debounce((val) => searchMembers(val), 300);
            searchEl.addEventListener('input', (e) => debounced(e.target.value.trim()));

            // Toggle selection via row or checkbox
            listEl.addEventListener('click', (e) => {
                const li = e.target.closest('li.list-group-item');
                if (!li) return;
                const id = parseInt(li.getAttribute('data-user-id'));
                const cb = li.querySelector('input[type="checkbox"]');
                const willSelect = !selectedIds.has(id);
                if (willSelect) { selectedIds.add(id); li.classList.add('selected'); cb.checked = true; }
                else { selectedIds.delete(id); li.classList.remove('selected'); cb.checked = false; }
                updateMinuting();
            });
            listEl.addEventListener('change', (e) => {
                const cb = e.target.closest('input[type="checkbox"]');
                if (!cb) return;
                const li = e.target.closest('li.list-group-item');
                const id = parseInt(li.getAttribute('data-user-id'));
                if (cb.checked) { selectedIds.add(id); li.classList.add('selected'); }
                else { selectedIds.delete(id); li.classList.remove('selected'); }
                updateMinuting();
            });

            function openModal() {
                updateMinuting();
                // Try Bootstrap modal if available, else fallback
                if (window.$ && typeof window.$('#shareModal').modal === 'function') {
                    window.$('#shareModal').modal('show');
                } else {
                    const modal = document.getElementById('shareModal');
                    modal.classList.add('show');
                    modal.style.display = 'block';
                    modal.removeAttribute('aria-hidden');
                }
            }

            shareBtn.addEventListener('click', async () => {
                openModal();
                await searchMembers('');
            });

            // Ensure both close buttons dismiss the modal completely
            [headerCloseBtn, footerCloseBtn].forEach(btn => {
                if (!btn) return;
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeShareModal();
                });
            });

            // When the recipient selection step is completed, auto-scroll to Minuting
            proceedBtn.addEventListener('click', () => {
                const selected = Array.from(selectedIds);
                if (!selected.length) {
                    alert('Select at least one member to continue.');
                    return;
                }
                // Close the selection modal
                if (window.$ && typeof window.$('#shareModal').modal === 'function') {
                    window.$('#shareModal').modal('hide');
                } else {
                    const modal = document.getElementById('shareModal');
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                }
                // Reveal Minuting section and then smooth-scroll to it
                updateMinuting();

                setTimeout(() => {
                    const target = document.getElementById('minutingSection');
                    if (!target) return;
                    // Account for possible fixed headers on desktop/mobile
                    const fixedHeader = document.querySelector('.navbar.fixed-top, .navbar.navbar-fixed-top, header.fixed-top');
                    const headerHeight = fixedHeader ? fixedHeader.offsetHeight : 0;
                    const rect = target.getBoundingClientRect();
                    const top = rect.top + window.pageYOffset - headerHeight - 8; // small offset
                    window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
                }, 150);
            });

            // Minuting textarea character counter
            if (msgEl && msgCountEl) {
                const updateCount = () => { msgCountEl.textContent = String(msgEl.value.length); };
                msgEl.addEventListener('input', updateCount);
                updateCount();
            }

            // Speech-to-Text: Web Speech API integration
            (function() {
                const sttBtn = document.getElementById('sttToggle');
                const sttStatus = document.getElementById('sttStatus');
                if (!sttBtn || !msgEl || !sttStatus) return;

                const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                let recognition = null;
                let isListening = false; // state governed by onstart/onend
                let isStarting = false;  // guard to avoid double .start()

                function setStatus(text) { sttStatus.textContent = text; }
                function updateBtnUI() {
                    sttBtn.classList.toggle('listening', isListening);
                    sttBtn.setAttribute('aria-pressed', String(isListening));
                    sttBtn.title = isListening ? 'Listening… Click to stop' : 'Click to speak';
                    setStatus(isListening ? 'Listening…' : 'Click to speak');
                }
                function caretToEnd() {
                    try {
                        msgEl.selectionStart = msgEl.value.length;
                        msgEl.selectionEnd = msgEl.value.length;
                    } catch (_) {}
                }

                // Require secure context (HTTPS) or localhost for mic permissions in modern browsers
                const isLocalHost = ['localhost', '127.0.0.1', '[::1]'].includes(location.hostname);
                const isSecure = window.isSecureContext || (location.protocol === 'https:' || isLocalHost);
                if (!isSecure) {
                    sttBtn.disabled = true;
                    sttBtn.classList.remove('listening');
                    sttBtn.title = 'Speech requires HTTPS or localhost';
                    setStatus('Speech requires HTTPS or localhost');
                    return;
                }

                if (!SR) {
                    sttBtn.disabled = true;
                    sttBtn.classList.remove('listening');
                    sttBtn.title = 'Speech recognition not supported';
                    setStatus('Speech recognition not supported');
                    return;
                }

                recognition = new SR();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.lang = document.documentElement.lang || 'en-US';

                recognition.onstart = () => { isStarting = false; isListening = true; updateBtnUI(); };
                recognition.onaudiostart = () => setStatus('Listening…');
                recognition.onspeechend = () => setStatus('Processing…');
                recognition.onend = () => { isStarting = false; isListening = false; updateBtnUI(); };
                recognition.onerror = (e) => {
                    console.error('SpeechRecognition error:', e);
                    isStarting = false;
                    isListening = false;
                    updateBtnUI();
                    // Provide clear, actionable messages per error type
                    switch (e.error) {
                        case 'not-allowed':
                        case 'service-not-allowed':
                            showFeedback('warning', 'Microphone permission denied. Allow mic in browser site settings.');
                            setStatus('Permission denied');
                            break;
                        case 'audio-capture':
                            showFeedback('warning', 'No microphone found or in use by another app.');
                            setStatus('No microphone');
                            break;
                        case 'network':
                            showFeedback('warning', 'Network error starting speech service. Check connection.');
                            setStatus('Network error');
                            break;
                        case 'no-speech':
                            setStatus('No speech detected');
                            break;
                        case 'aborted':
                            setStatus('Listening stopped');
                            break;
                        default:
                            showFeedback('warning', e?.message || 'Microphone error. Check permissions.');
                            setStatus('Error');
                            break;
                    }
                };
                recognition.onresult = (event) => {
                    let finalTranscript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const res = event.results[i];
                        if (res.isFinal) finalTranscript += res[0].transcript;
                    }
                    if (finalTranscript) {
                        const needsSpace = msgEl.value && !msgEl.value.endsWith(' ');
                        msgEl.value += (needsSpace ? ' ' : '') + finalTranscript.trim();
                        caretToEnd();
                        msgEl.dispatchEvent(new Event('input'));
                    }
                };

                function toggleListening() {
                    if (!recognition) return;
                    try {
                        if (isListening) {
                            recognition.stop();
                        } else if (!isStarting) {
                            isStarting = true;
                            // Hint: calling start triggers a permission prompt on first use
                            recognition.start();
                            setStatus('Starting…');
                        }
                    } catch (err) {
                        console.error(err);
                        const msg = /not allowed/i.test(err?.message || '') ?
                            'Microphone permission denied. Allow mic in browser site settings.' :
                            'Unable to start microphone.';
                        showFeedback('warning', msg);
                        setStatus('Error');
                        isStarting = false;
                    }
                }

                sttBtn.addEventListener('click', toggleListening);
                sttBtn.addEventListener('keydown', (e) => {
                    if (e.code === 'Space' || e.key === ' ') { e.preventDefault(); toggleListening(); }
                });

                // Stop listening when the page/tab becomes hidden
                document.addEventListener('visibilitychange', () => { if (document.hidden && isListening) recognition.stop(); });
            })();

            // Send / Share button integration
            if (sendBtn) {
                sendBtn.addEventListener('click', async () => {
                    const recipients = Array.from(selectedIds);
                    const message = (msgEl?.value || '').trim();

                    // Basic client-side validation for better UX
                    if (!recipients.length) {
                        showFeedback('danger', 'Please select at least one recipient.');
                        return;
                    }
                    if (!message) {
                        showFeedback('danger', 'Please enter a minuting message before sending.');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('_token', csrfToken);
                    fd.append('document_id', `{{ $document_received->document->id }}`);
                    fd.append('message', message);
                    recipients.forEach(id => fd.append('recipient_id[]', String(id)));

                    const prevText = sendBtn.textContent;
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Sending…';
                    hideFeedback();

                    try {
                        const res = await fetch(sendDocUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: fd,
                        });

                        const contentType = res.headers.get('content-type') || '';
                        if (res.ok) {
                            showFeedback('success', 'Document sent successfully.');
                            closeShareModal();
                            setTimeout(() => { window.location.reload(); }, 1200);
                            return;
                        }

                        if (contentType.includes('application/json')) {
                            const data = await res.json().catch(() => null);
                            const errors = (data && (data.errors || data.error)) || null;
                            if (errors) {
                                const msg = normalizeErrors(errors);
                                showFeedback('danger', msg);
                            } else {
                                showFeedback('danger', data?.message || 'Failed to send document.');
                            }
                        } else {
                            showFeedback('danger', 'Failed to send document. Please try again.');
                        }
                    } catch (err) {
                        console.error(err);
                        showFeedback('danger', 'Network error. Please check your connection and try again.');
                    } finally {
                        sendBtn.disabled = false;
                        sendBtn.textContent = prevText;
                    }
                });
            }

            function showFeedback(type, message) {
                if (!feedbackEl) return;
                feedbackEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
                const cls = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger';
                feedbackEl.classList.add(cls);
                feedbackEl.textContent = message;
            }
            function hideFeedback() { if (feedbackEl) { feedbackEl.classList.add('d-none'); feedbackEl.textContent = ''; } }
            function normalizeErrors(errors) {
                if (Array.isArray(errors)) return errors.join('; ');
                if (typeof errors === 'string') return errors;
                const msgs = [];
                for (const key in errors) {
                    const val = errors[key];
                    if (Array.isArray(val)) msgs.push(...val);
                    else if (typeof val === 'string') msgs.push(val);
                }
                return msgs.length ? msgs.join('\n') : 'Validation failed.';
            }
            function closeShareModal() {
                const modal = document.getElementById('shareModal');
                if (window.$ && typeof window.$('#shareModal').modal === 'function') {
                    window.$('#shareModal').modal('hide');
                } else if (modal) {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                }
            }
        })();
    </script>
@endsection