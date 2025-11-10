@extends('dashboards.index')

@section('content')
    <div class="container-fluid pt-4 px-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="bg-light rounded p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h6 class="mb-4">Tenant Cleanup</h6>
                        <div>
                            <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i
                                    class="fa fa-arrow-left me-2"></i>Back</a>
                        </div>
                    </div>

                    <form action="{{ route('superadmin.tenant.cleanup.run') }}" method="POST" id="tenantCleanupForm">
                        @csrf
                        <div class="row">
                            <div class="col-sm-12 col-xl-6 mb-3">
                                <label class="form-label" for="tenant_id">Select Tenant:</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" required>
                                    <option value="">Select tenant</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                                @error('tenant_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-warning" role="alert">
                            <p class="mb-1">This operation will:</p>
                            <ul class="mb-0">
                                <li>Delete file movements for the tenant’s users and documents.</li>
                                <li>Delete document recipients linked to those movements and tenant users.</li>
                                <li>Delete documents uploaded by tenant users.</li>
                                <li>Delete memos authored by tenant users.</li>
                                <li>Delete memo movements and their recipients tied to tenant users.</li>
                                <li>Delete all folders belonging to the selected tenant.</li>
                                <li>Delete folder permissions linked to those folders.</li>
                            </ul>
                            <small class="text-muted d-block mt-2">Note: External file storage (e.g., Cloudinary) is not modified by this operation.</small>
                        </div>

                        <button type="submit" class="btn btn-danger" id="cleanupBtn">Run Cleanup</button>
                        <span id="progressMsg" class="text-muted ms-2 d-none">Processing cleanup… please wait.</span>
                    </form>

                    @if(session('cleanup_stats'))
                        <div class="mt-4">
                            <div class="alert alert-success">
                                <strong>Cleanup Results</strong>
                                <ul class="mb-0 mt-2">
                                    <li>File Movements: {{ session('cleanup_stats')['file_movements_deleted'] ?? 0 }}</li>
                                    <li>Documents: {{ session('cleanup_stats')['documents_deleted'] ?? 0 }}</li>
                                    <li>Document Recipients: {{ session('cleanup_stats')['document_recipients_deleted'] ?? 0 }}</li>
                                    <li>Activities: {{ session('cleanup_stats')['activities_deleted'] ?? 0 }}</li>
                                    <li>Memos: {{ session('cleanup_stats')['memos_deleted'] ?? 0 }}</li>
                                    <li>Memo Movements: {{ session('cleanup_stats')['memo_movements_deleted'] ?? 0 }}</li>
                                    <li>Memo Recipients: {{ session('cleanup_stats')['memo_recipients_deleted'] ?? 0 }}</li>
                                    <li>Folders: {{ session('cleanup_stats')['folders_deleted'] ?? 0 }}</li>
                                    <li>Folder Permissions: {{ session('cleanup_stats')['folder_permissions_deleted'] ?? 0 }}</li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('tenantCleanupForm').addEventListener('submit', function () {
            document.getElementById('cleanupBtn').disabled = true;
            document.getElementById('progressMsg').classList.remove('d-none');
        });
    </script>
@endsection