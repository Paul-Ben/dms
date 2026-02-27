{{-- @extends('dashboards.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            @if($folder->parent)
                                <a href="{{ route('folders.show', $folder->parent) }}" class="text-muted">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                            @endif
                            {{ $folder->name }}
                        </h3>
                        <div>
                            <a href="{{ route('folders.edit', $folder) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Folder
                            </a>
                            <a href="{{ route('folders.create', ['parent_id' => $folder->id]) }}" class="btn btn-success">
                                <i class="fas fa-folder-plus"></i> New Subfolder
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($folder->description)
                        <div class="alert alert-info">
                            {{ $folder->description }}
                        </div>
                    @endif

                    <div class="row">
                        <!-- Subfolders Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Subfolders</h4>
                                </div>
                                <div class="card-body">
                                    @if($folder->children->count() > 0)
                                        <div class="list-group">
                                            @foreach($folder->children as $subfolder)
                                                <a href="{{ route('folders.show', $subfolder) }}" 
                                                   class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h5 class="mb-1">
                                                            <i class="fas fa-folder text-warning"></i>
                                                            {{ $subfolder->name }}
                                                        </h5>
                                                        <small>
                                                            {{ $subfolder->documents_count }} documents
                                                        </small>
                                                    </div>
                                                    @if($subfolder->description)
                                                        <p class="mb-1">{{ Str::limit($subfolder->description, 100) }}</p>
                                                    @endif
                                                    <small>
                                                        Created by {{ $subfolder->creator->name }}
                                                        @if($subfolder->is_private)
                                                            <span class="badge badge-info">Private</span>
                                                        @endif
                                                    </small>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No subfolders found.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Documents Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Documents</h4>
                                </div>
                                <div class="card-body">
                                    @if($folder->documents->count() > 0)
                                        <div class="list-group">
                                            @foreach($folder->documents as $document)
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h5 class="mb-1">
                                                            <i class="fas fa-file text-primary"></i>
                                                            <a href="{{ route('document.view', $document->id) }}" class="text-decoration-none">
                                                                {{ $document->title }}
                                                            </a>
                                                        </h5>
                                                        <small>
                                                            {{ $document->created_at->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                    <p class="mb-1">{{ $document->docuent_number }} - {{ Str::limit($document->description, 100) }}</p>
                                                    <small>
                                                        @if($document->is_private)
                                                            <span class="badge badge-info">Private</span>
                                                        @endif
                                                    </small>
                                                    <div class="btn-group">
                                                        <a href="{{ route('document.view', $document) }}" class="btn btn-sm btn-info">
                                                            <i class="fa fa-eye"></i> View
                                                        </a>
                                                        <a href="{{ route('folders.remove-document', ['folder' => $folder->id, 'document' => $document->id]) }}" class="btn btn-sm btn-danger">
                                                            <i class="fa fa-trash"></i> Remove
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No documents found in this folder.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($folder->is_private)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="card-title">Folder Permissions</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Permission</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($folder->permissions as $permission)
                                                <tr>
                                                    <td>{{ $permission->user->name }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $permission->permission === 'admin' ? 'danger' : ($permission->permission === 'write' ? 'warning' : 'info') }}">
                                                            {{ ucfirst($permission->permission) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection  --}}

@extends('dashboards.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            @if($folder->parent)
                                <a href="{{ route('folders.show', $folder->parent) }}" class="text-muted mr-2">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                            @endif
                            <i class="fas fa-folder text-warning mr-2"></i>
                            {{ $folder->name }}
                        </h3>
                        <div>
                            <a href="{{ route('folders.edit', $folder) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="{{ route('folders.create', ['parent_id' => $folder->id]) }}" class="btn btn-sm btn-success ml-2">
                                <i class="fas fa-folder-plus"></i> New Subfolder
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($folder->description)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>{{ $folder->description }}
                        </div>
                    @endif

                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="folderTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="subfolders-tab" data-toggle="tab" href="#subfolders" role="tab">
                                <i class="fas fa-folder mr-1"></i> Subfolders ({{ $folder->children->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab">
                                <i class="fas fa-file mr-1"></i> Documents ({{ $folder->documents->count() }})
                            </a>
                        </li>
                        @if($folder->is_private)
                        <li class="nav-item">
                            <a class="nav-link" id="permissions-tab" data-toggle="tab" href="#permissions" role="tab">
                                <i class="fas fa-key mr-1"></i> Permissions
                            </a>
                        </li>
                        @endif
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content mt-3" id="folderTabsContent">
                        <!-- Subfolders Tab -->
                        <div class="tab-pane fade show active" id="subfolders" role="tabpanel">
                            @if($folder->children->count() > 0)
                                <div class="row">
                                    @foreach($folder->children as $subfolder)
                                        <div class="col-md-6 mb-3">
                                            <div class="card folder-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h5 class="card-title">
                                                                <a href="{{ route('folders.show', $subfolder) }}" class="text-decoration-none">
                                                                    <i class="fas fa-folder text-warning mr-2"></i>
                                                                    {{ $subfolder->name }}
                                                                </a>
                                                            </h5>
                                                            @if($subfolder->description)
                                                                <p class="card-text text-muted">
                                                                    {{ Str::limit($subfolder->description, 100) }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <span class="badge badge-light">
                                                            {{ $subfolder->documents_count }} docs
                                                        </span>
                                                    </div>
                                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="fas fa-user mr-1"></i>
                                                            {{ $subfolder->creator->name }}
                                                        </small>
                                                        @if($subfolder->is_private)
                                                            <span class="badge badge-info">Private</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-light text-center">
                                    <i class="fas fa-folder-open fa-2x mb-3 text-muted"></i>
                                    <h5>No subfolders found</h5>
                                    <a href="{{ route('folders.create', ['parent_id' => $folder->id]) }}" class="btn btn-sm btn-success">
                                        <i class="fas fa-folder-plus mr-1"></i> Create Subfolder
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            @if($folder->documents->count() > 0)
                                <div class="list-group">
                                    @foreach($folder->documents as $document)
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <i class="fas fa-file-alt text-primary mr-2"></i>
                                                        <a href="{{ route('document.myview', $document) }}" class="text-decoration-none">
                                                            {{ $document->title }}
                                                        </a>
                                                    </h5>
                                                    <small class="text-muted">
                                                        {{ $document->docuent_number }}
                                                    </small>
                                                    @if($document->description)
                                                        <p class="mb-1 mt-2">{{ Str::limit($document->description, 150) }}</p>
                                                    @endif
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="docDropdown{{ $document->id }}" data-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="{{ route('document.myview', $document) }}">
                                                            <i class="fas fa-eye mr-2"></i> View
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('folders.remove-document', ['folder' => $folder->id, 'document' => $document->id]) }}" 
                                                           onclick="return confirm('Are you sure you want to remove this document?')">
                                                            <i class="fas fa-trash-alt mr-2"></i> Remove
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted">
                                                    <i class="far fa-clock mr-1"></i>
                                                    {{ $document->created_at->diffForHumans() }}
                                                </small>
                                                @if($document->is_private)
                                                    <span class="badge badge-info">Private</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-light text-center">
                                    <i class="fas fa-file-alt fa-2x mb-3 text-muted"></i>
                                    <h5>No documents in this folder</h5>
                                    <p class="text-muted">Upload or move documents to this folder</p>
                                </div>
                            @endif
                        </div>

                        <!-- Permissions Tab (only for private folders) -->
                        @if($folder->is_private)
                        <div class="tab-pane fade" id="permissions" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>User</th>
                                                    <th>Permission</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($folder->permissions as $permission)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-sm bg-light rounded-circle mr-2 d-flex align-items-center justify-content-center">
                                                                    {{ strtoupper(substr($permission->user->name, 0, 1)) }}
                                                                </div>
                                                                {{ $permission->user->name }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-{{ $permission->permission === 'admin' ? 'danger' : ($permission->permission === 'write' ? 'warning' : 'info') }}">
                                                                {{ ucfirst($permission->permission) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('folders.permissions', $folder) }}" 
                                                               class="btn btn-sm btn-outline-primary" title="Manage Permissions">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('folders.permissions', $folder) }}" class="btn btn-primary">
                                            <i class="fas fa-user-plus mr-1"></i> Manage Permissions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .folder-card {
        transition: all 0.3s ease;
        border-left: 4px solid #ffc107;
    }
    .folder-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .avatar-sm {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    .nav-tabs .nav-link.active {
        font-weight: 600;
        border-bottom: 3px solid #4e73df;
    }
</style>

@endsection