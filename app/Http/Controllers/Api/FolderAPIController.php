<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FolderAPIController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = optional($user->userDetail)->tenant_id;

        if (!$tenantId) {
            return response()->json(['message' => 'User is not linked to a tenant'], 422);
        }

        $folders = Folder::with(['creator:id,name', 'parent:id,name', 'users:id,name'])
            ->withCount(['documents', 'children'])
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('users', function ($uq) use ($user) {
                        $uq->where('users.id', $user->id);
                    });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->query('search'));
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('creator', function ($cq) use ($search) {
                            $cq->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($request->filled('privacy'), function ($q) use ($request) {
                if ($request->query('privacy') === 'private') {
                    $q->where('is_private', true);
                }
                if ($request->query('privacy') === 'public') {
                    $q->where('is_private', false);
                }
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 10));

        return response()->json(['data' => $folders], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $tenantId = optional($user->userDetail)->tenant_id;

        if (!$tenantId) {
            return response()->json(['message' => 'User is not linked to a tenant'], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'is_private' => ['sometimes', 'boolean'],
            'permissions' => ['required_if:is_private,true', 'array'],
            'permissions.*.user_id' => ['required', 'exists:users,id'],
            'permissions.*.permission' => ['required', Rule::in(['read', 'write', 'admin'])],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Folder::find($validated['parent_id']);
            if (!$parent || (int) $parent->tenant_id !== (int) $tenantId) {
                return response()->json(['message' => 'Parent folder must belong to the same tenant'], 422);
            }
        }

        $folder = DB::transaction(function () use ($validated, $tenantId, $user) {
            $folder = Folder::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'tenant_id' => $tenantId,
                'created_by' => $user->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'is_private' => (bool) ($validated['is_private'] ?? false),
            ]);

            $folder->users()->attach($user->id, ['permission' => 'admin']);

            if (!empty($validated['is_private']) && !empty($validated['permissions'])) {
                foreach ($validated['permissions'] as $permission) {
                    if ((int) $permission['user_id'] === (int) $user->id) {
                        continue;
                    }
                    $folder->users()->syncWithoutDetaching([
                        $permission['user_id'] => ['permission' => $permission['permission']],
                    ]);
                }
            }

            return $folder;
        });

        return response()->json([
            'message' => 'Folder created successfully',
            'data' => $folder->load(['creator:id,name', 'parent:id,name', 'users:id,name']),
        ], 201);
    }

    public function show(Folder $folder)
    {
        $user = Auth::user();

        if (!$this->canAccessFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized access to folder'], 403);
        }

        $folder->load([
            'creator:id,name',
            'parent:id,name',
            'children:id,name,parent_id,tenant_id',
            'documents',
            'users:id,name',
        ]);

        return response()->json(['data' => $folder], 200);
    }

    public function update(Request $request, Folder $folder)
    {
        $user = Auth::user();

        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to update folder'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        if (!empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === (int) $folder->id) {
                return response()->json(['message' => 'A folder cannot be its own parent'], 422);
            }
            $parent = Folder::find($validated['parent_id']);
            if (!$parent || (int) $parent->tenant_id !== (int) $folder->tenant_id) {
                return response()->json(['message' => 'Parent folder must belong to the same tenant'], 422);
            }
        }

        $folder->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Folder updated successfully',
            'data' => $folder->fresh()->load(['creator:id,name', 'parent:id,name', 'users:id,name']),
        ], 200);
    }

    public function destroy(Folder $folder)
    {
        $user = Auth::user();

        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to delete folder'], 403);
        }

        if ($folder->documents()->exists()) {
            return response()->json(['message' => 'Cannot delete folder with documents'], 422);
        }

        if ($folder->children()->exists()) {
            return response()->json(['message' => 'Cannot delete folder with subfolders'], 422);
        }

        $folder->delete();

        return response()->json(['message' => 'Folder deleted successfully'], 200);
    }

    public function move(Request $request, Folder $folder)
    {
        $user = Auth::user();
        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to move folder'], 403);
        }

        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        if (!empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === (int) $folder->id) {
                return response()->json(['message' => 'A folder cannot be its own parent'], 422);
            }
            $parent = Folder::find($validated['parent_id']);
            if (!$parent || (int) $parent->tenant_id !== (int) $folder->tenant_id) {
                return response()->json(['message' => 'Parent folder must belong to the same tenant'], 422);
            }
        }

        $folder->update(['parent_id' => $validated['parent_id'] ?? null]);

        return response()->json([
            'message' => 'Folder moved successfully',
            'data' => $folder->fresh(),
        ], 200);
    }

    public function share(Request $request, Folder $folder)
    {
        $user = Auth::user();
        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to share folder'], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'permission' => ['required', Rule::in(['read', 'write', 'admin'])],
        ]);

        foreach ($validated['user_ids'] as $userId) {
            if ((int) $userId === (int) $folder->created_by) {
                continue;
            }
            $folder->users()->syncWithoutDetaching([
                $userId => ['permission' => $validated['permission']],
            ]);
        }

        return response()->json(['message' => 'Folder shared successfully'], 200);
    }

    public function unshare(Request $request, Folder $folder)
    {
        $user = Auth::user();
        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to unshare folder'], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'exists:users,id'],
        ]);

        $folder->users()->detach($validated['user_ids']);

        return response()->json(['message' => 'Folder unshared successfully'], 200);
    }

    public function permissions(Folder $folder)
    {
        $user = Auth::user();
        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to view permissions'], 403);
        }

        $folder->load(['users:id,name,email']);
        $permissions = $folder->users->map(function ($member) {
            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'permission' => $member->pivot->permission,
            ];
        });

        return response()->json([
            'data' => [
                'folder_id' => $folder->id,
                'is_private' => (bool) $folder->is_private,
                'permissions' => $permissions,
            ],
        ], 200);
    }

    public function updatePermissions(Request $request, Folder $folder)
    {
        $user = Auth::user();
        if (!$this->canManageFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to update permissions'], 403);
        }

        $validated = $request->validate([
            'is_private' => ['sometimes', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.user_id' => ['required', 'exists:users,id'],
            'permissions.*.permission' => ['required', Rule::in(['read', 'write', 'admin'])],
        ]);

        DB::transaction(function () use ($folder, $validated, $user) {
            if (array_key_exists('is_private', $validated)) {
                $folder->update(['is_private' => (bool) $validated['is_private']]);
            }

            $folder->users()->detach();
            $folder->users()->attach($user->id, ['permission' => 'admin']);

            if (!empty($validated['permissions'])) {
                foreach ($validated['permissions'] as $permission) {
                    if ((int) $permission['user_id'] === (int) $user->id) {
                        continue;
                    }
                    $folder->users()->syncWithoutDetaching([
                        $permission['user_id'] => ['permission' => $permission['permission']],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Folder permissions updated successfully'], 200);
    }

    public function addDocument(Request $request, Folder $folder)
    {
        $user = Auth::user();
        if (!$this->canWriteToFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to add documents to folder'], 403);
        }

        $validated = $request->validate([
            'document_id' => ['required', 'exists:documents,id'],
        ]);

        $document = Document::findOrFail($validated['document_id']);

        if (!empty($document->folder_id) && (int) $document->folder_id !== (int) $folder->id) {
            return response()->json(['message' => 'Document already belongs to another folder'], 422);
        }

        $document->update(['folder_id' => $folder->id]);

        return response()->json(['message' => 'Document added to folder successfully'], 200);
    }

    public function addDocuments(Request $request, Folder $folder)
    {
        return $this->addDocument($request, $folder);
    }

    public function removeDocument(Request $request, Folder $folder, Document $document)
    {
        $user = Auth::user();
        if (!$this->canWriteToFolder($folder, $user)) {
            return response()->json(['message' => 'Unauthorized to remove documents from folder'], 403);
        }

        if ((int) $document->folder_id !== (int) $folder->id) {
            return response()->json(['message' => 'Document does not belong to this folder'], 422);
        }

        $document->update(['folder_id' => null]);

        return response()->json(['message' => 'Document removed from folder successfully'], 200);
    }

    private function canAccessFolder(Folder $folder, $user): bool
    {
        $tenantId = optional($user->userDetail)->tenant_id;
        if (!$tenantId || (int) $folder->tenant_id !== (int) $tenantId) {
            return false;
        }

        if ((int) $folder->created_by === (int) $user->id) {
            return true;
        }

        if (!$folder->is_private) {
            return true;
        }

        return $folder->users()->where('users.id', $user->id)->exists();
    }

    private function canManageFolder(Folder $folder, $user): bool
    {
        if (!$this->canAccessFolder($folder, $user)) {
            return false;
        }

        if ((int) $folder->created_by === (int) $user->id) {
            return true;
        }

        return in_array($user->default_role, ['Admin', 'superadmin'], true);
    }

    private function canWriteToFolder(Folder $folder, $user): bool
    {
        if (!$this->canAccessFolder($folder, $user)) {
            return false;
        }

        if ((int) $folder->created_by === (int) $user->id) {
            return true;
        }

        if (!$folder->is_private) {
            return true;
        }

        $permission = $folder->users()
            ->where('users.id', $user->id)
            ->value('folder_permissions.permission');

        return in_array($permission, ['write', 'admin'], true);
    }
}
