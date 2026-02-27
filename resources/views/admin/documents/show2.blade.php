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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

        @media (max-width: 576px) {
            .toolbar {
                gap: 6px;
            }

            .btn {
                padding: 5px 8px;
                font-size: 12px;
            }

            .btn svg {
                width: 12px;
                height: 12px;
            }

            .email-meta {
                grid-template-columns: 60px 1fr;
                font-size: 12px;
                gap: 6px;
            }

            .timeline {
                padding-left: 30px;
            }

            .timeline-item::before {
                left: -25px;
                width: 8px;
                height: 8px;
            }
        }
    </style>

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
                    {{-- <button class="btn" type="button" data-toggle="modal" data-target="#forwardedMessageModal">
                        <svg viewBox="0 0 24 24">
                            <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                        </svg>
                        <span class="d-none d-md-inline">Previous Minuting</span>
                        <span class="d-inline d-md-none">History</span>
                    </button> --}}
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
                        <a href="{{ route('getAttachments', $document_received->document_id) }}"
                            class="text-decoration-none">
                            <button class="btn">
                                <svg viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
                                </svg>
                                <span class="d-none d-sm-inline">Attachments</span>
                                <span class="d-inline d-sm-none">Files</span>
                            </button>
                        </a>
                    @endif

                    <a href="{{ route('folders.select', $document_received->document->id) }}" class="text-decoration-none"
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

                <!-- Document Preview Section -->
                <div class="attachment mt-3">
                    <div class="file-details">
                        <strong>Document:</strong> {{ e($document_received->document->docuent_number) }} |

                        <div id="previewContainer" class="mt-3"
                            style="display: flex; flex-direction: column; height: 90vh; min-height: 300px;">
                            {{-- <iframe id="pdfPreview" style="flex: 1 1 auto; height: 1000px; width: 100%; border: none;"
                                src="{{ $document_received->document->file_path) }}">
                            </iframe> --}}
                            {{-- <iframe id="pdfPreview" style="flex: 1 1 auto; height: 1000px; width: 100%; border: none;"
                                src="{{ asset('storage/' . $document_received->document->file_path) }}">
                            </iframe> --}}
                            @php
                                // Determine if the stored file_path is a full URL or a relative path
                                $fileUrl = $document_received->document->file_path;

                                // Basic check if the file_path starts with http:// or https:// --> means full URL (e.g. Cloudinary)
                                if (!preg_match('/^https?:\/\//', $fileUrl)) {
                                    // If relative path assumed, generate full URL via asset helper
                                    $fileUrl = asset('storage/' . $fileUrl);
                                }
                            @endphp

                            <iframe id="pdfPreview" style="flex: 1 1 auto; height: 1000px; width: 100%; border: none;"
                                src="{{ $fileUrl }}"></iframe>

                        </div>
                    </div>
                </div>

                <!-- Attachments List -->
                <div class="mt-3">
                    @if ($document_received->attachments->isNotEmpty())
                            <a href="{{ $document_received->attachments[0]->attachment }}"
                            target="__blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-paperclip mr-1"></i>
                            Attachment sent by {{ $document_received->sender->name }}
                        </a>
                    @endif
                </div>

                <!-- Document Download Section -->
                <div class="attachment mt-3" id="fileinput">
                    <div class="file-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path fill="#5f6368"
                                d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                        </svg>
                    </div>
                    <div class="file-details">
                        <div class="file-name">
                            <a href="{{ $document_received->document->file_path }}" download="true" target="__blank"
                                class="text-primary">
                                {{ $document_received->document->docuent_number }}
                            </a>
                        </div>
                        <div class="file-meta">PDF Document Added
                            {{ $document_received->updated_at->format('M j, Y') }}</div>
                    </div>
                </div>
            </div>

            <!-- Previous Minuting Timeline Section -->
            <div class="container py-3 py-md-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <!-- Header -->
                        <div id="priviousmiuting" class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 h4-md fw-bold text-primary mb-0">
                                <i class="fas fa-history mr-2"></i> Previous Minuting
                            </h2>
                        </div>

                        <!-- Document Info Card -->
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-body bg-light p-3">
                                <h5 class="card-title fw-bold mb-1">
                                    Document Title: {{ $document_received->document->title }}
                                </h5>
                                <p class="card-text text-muted small mb-0">
                                    <strong>Document #:</strong> {{ $document_received->document->docuent_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Chat-Style Timeline -->
                        <div class="timeline">
                            @foreach ($document_locations as $location)
                                {{-- <div class="timeline-item">
                                    <!-- Message Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white rounded-circle mr-2 mr-md-3">
                                                {{ strtoupper(substr($location->sender->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold small">{{ $location->sender->name }}</h6>
                                                <small class="text-muted d-block">
                                                    {{ $location->sender->userDetail->designation }}
                                                </small>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ $location->created_at->format('M j, Y g:i A') }}
                                        </small>
                                    </div>

                                    <!-- Message Bubble -->
                                    <div class="message-bubble">
                                        <p class="mb-2 small">Hi {{ $location->recipient->name }},</p>
                                        <p class="mb-3 small">{{ $location->message }}</p>
                                        <small class="text-muted d-block small">
                                            <i class="fas fa-user-check mr-1"></i>
                                            Sent to: {{ $location->recipient->name }}
                                        </small>
                                    </div>
                                </div> --}}
                                <div class="timeline-item">
                                    <!-- Clickable Header (acts as accordion toggle) -->
                                    <div class="message-header d-flex justify-content-between align-items-start mb-2"
                                        onclick="this.parentNode.querySelector('.message-content').classList.toggle('d-none')"
                                        style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white rounded-circle mr-2 mr-md-3">
                                                {{ strtoupper(substr($location->sender->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold small">{{ $location->sender->name }}</h6>
                                                <small class="text-muted d-block">
                                                    {{ $location->sender->userDetail->designation }}
                                                </small>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ $location->created_at->format('M j, Y g:i A') }}
                                        </small>
                                    </div>

                                    <!-- Collapsible Message Content -->
                                    <div class="message-content d-none animate__animated animate__fadeIn">
                                        <div class="message-bubble bg-light p-3 rounded mt-2">
                                            <p class="mb-2 small">Hi {{ $location->recipient->name }},</p>
                                            <p class="mb-3 small">{{ $location->message }}</p>
                                            <small class="text-muted d-block small">
                                                <i class="fas fa-user-check mr-1"></i>
                                                Sent to: {{ $location->recipient->name }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <style>
                                    .message-header:hover {
                                        background-color: #f8f9fa;
                                    }

                                    .message-bubble {
                                        border-left: 3px solid #0d6efd;
                                    }
                                </style>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="container py-3 py-md-4">
                <div class="row justify-content-center">
                    <div class="col-12">

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

    <script>
        function showPreview() {
            document.getElementById('previewModal').style.display = 'block';
        }

        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
            const modal = document.getElementById('previewModal');
            const pdfPreview = document.getElementById('pdfPreview');

            // Clear the iframe source when closing the modal
            pdfPreview.src = '';
            modal.style.display = 'none';
        }

        function replyEmail() {
            alert('Opening reply composer...');
        }

        function forwardEmail() {
            alert('Opening forward composer...');
        }

        function processEmail() {
            alert('Processing email...');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('previewModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function previewDocument(fileUrl, fileType) {
            if (fileType === 'pdf') {
                document.getElementById('imagePreview').style.display = 'none';
                const pdfPreview = document.getElementById('pdfPreview');
                pdfPreview.style.display = 'block';
                pdfPreview.src = fileUrl;
            } else if (fileType.match(/(jpg|jpeg|png)/)) {
                document.getElementById('pdfPreview').style.display = 'none';
                const imagePreview = document.getElementById('imagePreview');
                imagePreview.style.display = 'block';
                imagePreview.src = fileUrl;
            }
        }
    </script>
@endsection
