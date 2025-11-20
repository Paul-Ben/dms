<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentStorage;
use App\Helpers\CloudinaryHelper;
use App\Helpers\FileService;
use App\Helpers\SendMailHelper;
use App\Helpers\StampHelper;
use App\Helpers\UserAction;
use App\Models\Designation;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\FileMovement;
use App\Models\Tenant;
use App\Models\TenantDepartment;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendNotificationMail;
use App\Mail\ReceiveNotificationMail;
use App\Models\Activity;
use App\Models\Attachments;
use App\Models\DocumentHold;
use App\Models\FileCharge;
use App\Models\Memo;
use App\Models\MemoMovement;
use App\Models\MemoRecipient;
use App\Models\Folder;
use App\Models\FolderPermission;
use App\Models\MemoTemplate;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Response;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SuperAdminActions extends Controller
{
    public function __construct()
    {
        // In local/testing, allow dev helper methods without auth
        if (app()->environment(['local', 'testing'])) {
            $this->middleware('auth')->except([
                'devSubmitFileDocument',
                'devCompletePayment',
                'devTestPaymentInit',
                'devLastReference',
            ]);
        } else {
            $this->middleware('auth');
        }
    }


    /**
     * Server-side DataTables: Departments (Superadmin)
     */
    public function departmentsData(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->default_role !== 'superadmin') {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'email',
            3 => 'phone',
            4 => 'status',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = TenantDepartment::query();
        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%$searchValue%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('status', 'like', $like);
            });
        }

        $baseQuery->orderBy($orderColumn, $orderDir);
        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $csrf = csrf_token();
        $data = [];
        foreach ($rows as $i => $department) {
            $action = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . route('department.edit', $department) . '" class="dropdown-item">Edit</a>'
                .'<form action="' . route('department.delete', $department) . '" method="POST" onsubmit="return confirm(\'Are you sure?\');" style="display:inline">'
                .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="DELETE">'
                .'<button class="dropdown-item" style="background-color: rgb(235, 78, 78)" type="submit">Delete</button>'
                .'</form>'
                .'</div>'
                .'</div>';

            $data[] = [
                'index' => $indexStart + $i,
                'name' => e($department->name),
                'email' => e($department->email ?? ''),
                'phone' => e($department->phone ?? ''),
                'status' => e($department->status ?? ''),
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Server-side DataTables: Organisations (Tenants) for Superadmin
     */
    public function organisationsData(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->default_role !== 'superadmin') {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'email',
            3 => 'phone',
            4 => 'status',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = Tenant::query();
        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%$searchValue%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('status', 'like', $like);
            });
        }

        $baseQuery->orderBy($orderColumn, $orderDir);
        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $csrf = csrf_token();
        $data = [];
        foreach ($rows as $i => $tenant) {
            $nameLink = '<a href="' . route('organisation.departments', $tenant) . '">' . e($tenant->name) . '</a>';

            $action = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . route('organisation.edit', $tenant) . '" class="dropdown-item">Edit</a>'
                .'<form action="' . route('organisation.delete', $tenant) . '" method="POST" onsubmit="return confirm(\'Are you sure?\');" style="display:inline">'
                .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="DELETE">'
                .'<button class="dropdown-item" style="background-color: rgb(235, 78, 78)" type="submit">Delete</button>'
                .'</form>'
                .'</div>'
                .'</div>';

            $data[] = [
                'index' => $indexStart + $i,
                'name' => $nameLink,
                'email' => e($tenant->email ?? ''),
                'phone' => e($tenant->phone ?? ''),
                'status' => e($tenant->status ?? ''),
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Server-side DataTables: User Manager (Superadmin)
     */
    public function usermanagerData(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->default_role !== 'superadmin') {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'id',
            1 => 'users.name',
            2 => 'tenants.name', // shown under "Designation" in current UI
            3 => 'tenant_departments.name',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = UserDetails::query()
            ->with(['user', 'tenant', 'tenant_department']);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%$searchValue%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('designation', 'like', $like)
                  ->orWhereHas('user', function ($uq) use ($like) {
                      $uq->where('name', 'like', $like);
                  })
                  ->orWhereHas('tenant', function ($tq) use ($like) {
                      $tq->where('name', 'like', $like);
                  })
                  ->orWhereHas('tenant_department', function ($dq) use ($like) {
                      $dq->where('name', 'like', $like);
                  });
            });
        }

        // Sorting
        if ($orderColumn === 'users.name') {
            $baseQuery->leftJoin('users', 'users.id', '=', 'user_details.user_id')
                      ->orderBy('users.name', $orderDir)
                      ->select('user_details.*');
        } elseif ($orderColumn === 'tenants.name') {
            $baseQuery->leftJoin('tenants', 'tenants.id', '=', 'user_details.tenant_id')
                      ->orderBy('tenants.name', $orderDir)
                      ->select('user_details.*');
        } elseif ($orderColumn === 'tenant_departments.name') {
            $baseQuery->leftJoin('tenant_departments', 'tenant_departments.id', '=', 'user_details.tenant_department_id')
                      ->orderBy('tenant_departments.name', $orderDir)
                      ->select('user_details.*');
        } else {
            $baseQuery->orderBy($orderColumn, $orderDir);
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $csrf = csrf_token();
        $data = [];
        foreach ($rows as $i => $ud) {
            $u = $ud->user;
            $tenant = $ud->tenant; // shown under Designation column in current UI
            $dept = $ud->tenant_department;

            $action = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . route('user.edit', optional($u)->id) . '" class="dropdown-item">Edit</a>';

            if (optional($u)->is_active) {
                $action .= '<form method="POST" action="' . route('user.deactivate', optional($u)->id) . '" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to deactivate this user? They will not be able to log in until reactivated.\')">'
                        .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="PATCH">'
                        .'<button type="submit" class="dropdown-item text-warning" style="border: none; background: none; text-align: left; width: 100%;">Deactivate</button>'
                        .'</form>';
            } else {
                $action .= '<form method="POST" action="' . route('user.activate', optional($u)->id) . '" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to activate this user?\')">'
                        .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="PATCH">'
                        .'<button type="submit" class="dropdown-item text-success" style="border: none; background: none; text-align: left; width: 100%;">Activate</button>'
                        .'</form>';
            }

            if (is_null(optional($u)->email_verified_at)) {
                $action .= '<form method="POST" action="' . route('user.verify', optional($u)->id) . '" style="display: inline;" onsubmit="return confirm(\'Verify this account?\')">'
                        .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="PATCH">'
                        .'<button type="submit" class="dropdown-item" style="border: none; background: none; text-align: left; width: 100%;">Verify Account</button>'
                        .'</form>';
            } else {
                $action .= '<span class="dropdown-item text-muted">Verified</span>';
            }

            $action .= '</div></div>';

            $data[] = [
                'index' => $indexStart + $i,
                'name' => '<a href="' . route('user.view', optional($u)->id) . '">' . e(optional($u)->name) . '</a>',
                // Replicate current UI: "Designation" column shows organisation name
                'designation' => e(optional($tenant)->name ?? ''),
                'department' => e(optional($dept)->name ?? ($ud->designation ?? '')),
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Server-side DataTables: Visitor Activity (Superadmin)
     */
    public function visitorActivityData(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->default_role !== 'superadmin') {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'id',
            1 => 'users.name',
            2 => 'ip_address',
            3 => 'url',
            4 => 'browser',
            5 => 'device',
            6 => 'created_at',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = \App\Models\VisitorActivity::query()->with('user');
        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%$searchValue%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('ip_address', 'like', $like)
                  ->orWhere('url', 'like', $like)
                  ->orWhere('browser', 'like', $like)
                  ->orWhere('device', 'like', $like)
                  ->orWhereHas('user', function ($uq) use ($like) {
                      $uq->where('name', 'like', $like);
                  });
            });
        }

        // Sorting
        if ($orderColumn === 'users.name') {
            $baseQuery->leftJoin('users', 'users.id', '=', 'visitor_activities.user_id')
                      ->orderBy('users.name', $orderDir)
                      ->select('visitor_activities.*');
        } else {
            $baseQuery->orderBy($orderColumn, $orderDir);
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $data = [];
        foreach ($rows as $i => $activity) {
            $userName = optional($activity->user)->name ?? 'Guest';
            $url = $activity->url ?? 'N/A';
            $urlShort = e(\Illuminate\Support\Str::limit($url, 20));
            $urlAnchor = '<a href="#" title="' . e($url) . '">' . $urlShort . '</a>';

            $data[] = [
                'index' => $indexStart + $i,
                'visitor_name' => e($userName),
                'ip_address' => e($activity->ip_address ?? ''),
                'url' => $urlAnchor,
                'browser' => e($activity->browser ?? ''),
                'device' => e($activity->device ?? 'N/A'),
                'date' => e(optional($activity->created_at)->format('M j, Y g:i A')),
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Server-side DataTables: Designations (Superadmin)
     */
    public function designationsData(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->default_role, ['superadmin', 'Admin', 'IT Admin'])) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'id',
            1 => 'name',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = Designation::query();
        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%$searchValue%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('name', 'like', $like);
            });
        }

        $baseQuery->orderBy($orderColumn, $orderDir);
        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $csrf = csrf_token();
        $data = [];
        foreach ($rows as $i => $designation) {
            // Only superadmin sees edit/delete actions; others are view-only
            if ($user->default_role === 'superadmin') {
                $action = '<div class="nav-item dropdown">'
                    .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                    .'<div class="dropdown-menu">'
                    .'<a href="' . route('designation.edit', $designation) . '" class="dropdown-item">Edit</a>'
                    .'<form action="' . route('designation.delete', $designation) . '" method="POST" onsubmit="return confirm(\'Are you sure?\');" style="display:inline">'
                    .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="DELETE">'
                    .'<button class="dropdown-item" style="background-color: rgb(235, 78, 78)" type="submit">Delete</button>'
                    .'</form>'
                    .'</div>'
                    .'</div>';
            } else {
                $action = '-';
            }

            $data[] = [
                'index' => $indexStart + $i,
                'name' => e($designation->name),
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }





    /**
     * User management Actions Index/create/edit/show/delete
     */
    public function user_index(Request $request)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();

        if (Auth::user()->default_role === 'superadmin') {
            $users = User::with('userDetail')->orderBy('id', 'desc')->get();
            // dd($users);
            return view('superadmin.usermanager.index', compact('users', 'authUser', 'userTenant'));
        }

        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $id = Auth::user()->userDetail->tenant_id;
            $users = UserDetails::with('user')->where('tenant_id', $id)->get();

            return view('admin.usermanager.index', compact('users', 'authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser'));
    }


    public function user_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();

            return view('superadmin.usermanager.create', compact('organisations', 'roles', 'departments', 'designations', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $id = Auth::user()->userDetail->tenant_id;
            $departments = TenantDepartment::where('tenant_id', $id)->get();
            $designations = Designation::all();
            $roles = Role::whereNotIn('name', ['superadmin', 'User'])->get();

            return view('admin.usermanager.create', compact('departments', 'designations', 'roles', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser'));
    }

    public function getDepartments($organisationId)
    {
        $departments = TenantDepartment::where('tenant_id', $organisationId)->get();

        return response()->json($departments);
    }

    // public function user_store(Request $request)
    // {

    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8|confirmed',
    //         'nin_number' => 'required|string',

    //     ]);



    //     // Create a new user instance
    //     $user = new User();
    //     $user->name = $request->input('name');
    //     $user->email = $request->input('email');
    //     $user->password = Hash::make($request->input('password'));
    //     $user->default_role = $request->input('default_role');
    //     // Assign the user a role
    //     if ($request->input('default_role') === 'Admin') {

    //         $user->assignRole('Admin');
    //     }
    //     if ($request->input('default_role') === 'Secretary') {

    //         $user->assignRole('Secretary');
    //     }
    //     if ($request->input('default_role') === 'Staff') {

    //         $user->assignRole('Staff');
    //     }
    //     if ($request->input('default_role') === 'User') {

    //         $user->assignRole('User');
    //     }
    //     if ($request->input('default_role') === 'IT Admin') {

    //         $user->assignRole('IT Admin');
    //     }


    //     $user->save();


    //     // Create a new user detail instance
    //     $user->userDetail()->create([
    //         'user_id' => $user->id,
    //         'department_id' => $request->input('department_id'),
    //         'tenant_id' => $request->input('tenant_id'),
    //         'phone_number' => $request->input('phone_number'),
    //         'designation' => $request->input('designation'),
    //         // 'avatar' => $request->input('avatar'),
    //         'gender' => $request->input('gender'),
    //         'signature' => $request->input('signature') ?: null,
    //         'nin_number' => $request->input('nin_number'),
    //         'psn' => $request->input('psn'),
    //         'grade_level' => $request->input('grade_level'),
    //         'rank' => $request->input('rank'),
    //         'schedule' => $request->input('schedule'),
    //         'employment_date' => $request->input('employment_date'),
    //         'date_of_birth' => $request->input('date_of_birth'),

    //     ]);

    //     $notification = [
    //         'message' => 'User created successfully',
    //         'alert-type' => 'success'
    //     ];

    //     return redirect()->route('users.index')->with($notification);
    // }
    public function user_store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'nin_number' => 'required|string',

        ]);

        try {
            DB::beginTransaction();

            // // Handle file uploads
            // Initialize signature path
            $signaturePath = null;
            $oldSignaturePath = $user->userDetail->signature ?? null;

            // Handle signature upload
            if ($request->hasFile('signature')) {
                // Store new signature first
                $signaturePath = $request->file('signature')->store('signatures', 'public');

                // Delete old signature AFTER successful upload of new one
                if ($oldSignaturePath && Storage::disk('public')->exists($oldSignaturePath)) {
                    Storage::disk('public')->delete($oldSignaturePath);
                }
            } else {
                // Keep existing signature if no new one uploaded
                $signaturePath = $oldSignaturePath;
            }

            Log::info('Successfully uploaded signature');

            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'default_role' => $request->input('default_role'),
            ]);



            // Assign role
            $user->assignRole($request->input('default_role'));



            // Create user details
            $user->userDetail()->create([
                'department_id' => $request->input('department_id'),
                'tenant_id' => $request->input('tenant_id'),
                'phone_number' => $request->input('phone_number'),
                'designation' => $request->input('designation'),
                'gender' => $request->input('gender'),
                'signature' => $signaturePath,
                'nin_number' => $request->input('nin_number'),
                'psn' => $request->input('psn'),
                'grade_level' => $request->input('grade_level'),
                'rank' => $request->input('rank'),
                'schedule' => $request->input('schedule'),
                'employment_date' => $request->input('employment_date'),
                'date_of_birth' => $request->input('date_of_birth'),
                'user_id' => $user->id,

            ]);

            DB::commit();



            return redirect()->route('users.index')->with([
                'message' => 'User created successfully',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded files if transaction fails

            if (isset($signaturePath)) {
                Storage::disk('public')->delete($signaturePath);
            }

            return back()->withInput()->with([
                'message' => 'Error creating user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    public function user_show(Request $request, User $user)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $user->load('userDetail');
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.usermanager.show', compact('user', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {

            return view('admin.usermanager.show', compact('user', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function user_edit(User $user)
    {
        try {
            $authUser = Auth::user();
            $userdetails = UserDetails::where('user_id', $authUser->id)->first();
            $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
            if (Auth::user()->default_role === 'superadmin') {
                $user_details = User::with('userDetail')->where('id', $user->id)->first();
                list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
                $organisationName = optional($user->userDetail)->tenant->name ?? null;
                $tenantDepartments = TenantDepartment::all();
                return view('superadmin.usermanager.edit', compact('user', 'roles', 'organisations', 'organisationName', 'tenantDepartments', 'departments', 'designations', 'user_details', 'authUser', 'userTenant'));
            }
            if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {

                $user_details = User::with('userDetail')->where('id', $user->id)->first();
                list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
                // $designations = Designation::all();
                $roles = Role::whereNotIn('name', ['superadmin', 'User'])->get();
                // $organisations = optional($authUser->userDetail)->tenant;
                $organisationName = optional($authUser->userDetail)->tenant->name;
                $tenantDepartments = TenantDepartment::where('tenant_id', optional($authUser->userDetail)->tenant_id)->get();
                // dd($tenantDepartments);
                return view('admin.usermanager.edit', compact('user', 'roles', 'organisations', 'organisationName', 'tenantDepartments', 'designations', 'user_details', 'authUser', 'userTenant'));
            }
            // return view('errors.404', compact('authUser', 'userTenant'));
        } catch (\Exception $e) {
            Log::error('Error while fetching user details: ' . $e->getMessage());
            $notification = [
                'message' => 'Error while fetching user details',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }
    }

    public function user_update(Request $request, User $user)
    {
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'nin_number' => 'required|string',
            'phone_number' => 'required|string',
            'default_role' => 'required|string',
            'department_id' => 'required|exists:tenant_departments,id',
            'tenant_id' => 'required|exists:tenants,id',
            'designation' => 'required|string',
            'gender' => 'required|in:male,female',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'psn' => 'nullable|string',
            'grade_level' => 'nullable|string',
            'rank' => 'nullable|string',
            'schedule' => 'nullable|string',
            'employment_date' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // Handle signature upload
            $signaturePath = $user->userDetail->signature ?? null;

            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($signaturePath) {
                    Storage::disk('public')->delete($signaturePath);
                }
                $signaturePath = $request->file('signature')->store('signatures', 'public');
            }

            // Update user
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'default_role' => $validated['default_role'],
            ];


            if ($request->input('password') != $user->password) {
                $user->update([
                    'password' => Hash::make($request->input('password')),
                ]);
            }

            $user->update($userData);

            // Sync roles
            $user->syncRoles([$validated['default_role']]);

            // Update user details
            $userDetailData = [
                'department_id' => $validated['department_id'],
                'tenant_id' => $validated['tenant_id'],
                'phone_number' => $validated['phone_number'],
                'designation' => $validated['designation'],
                'gender' => $validated['gender'],
                'signature' => $signaturePath,
                'nin_number' => $validated['nin_number'],
                'psn' => $validated['psn'] ?? null,
                'grade_level' => $validated['grade_level'] ?? null,
                'rank' => $validated['rank'] ?? null,
                'schedule' => $validated['schedule'] ?? null,
                'employment_date' => $validated['employment_date'],
                'date_of_birth' => $validated['date_of_birth'],
            ];

            // Update or create user details
            if ($user->userDetail) {
                $user->userDetail->update($userDetailData);
            } else {
                $user->userDetail()->create($userDetailData);
            }

            DB::commit();

            return redirect()->route('users.index')->with([
                'message' => 'User updated successfully',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded file if transaction fails
            if ($request->hasFile('signature') && isset($signaturePath)) {
                Storage::disk('public')->delete($signaturePath);
            }

            return back()->withInput()->with([
                'message' => 'Error updating user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }


    public function user_delete(User $user)
    {
        $user->delete();
        return redirect()->route('user.index')->with('success', 'User  deleted successfully.');
    }

    public function user_deactivate(User $user)
    {
        $authUser = Auth::user();
        
        // Check if user is superadmin or IT Admin 
        if ($authUser->default_role !== 'superadmin' && $authUser->default_role !== 'IT Admin') {
            return redirect()->back()->with([
                'message' => 'Unauthorized action',
                'alert-type' => 'error'
            ]);
        }

        // Prevent self-deactivation
        if ($user->id === $authUser->id) {
            return redirect()->back()->with([
                'message' => 'You cannot deactivate your own account',
                'alert-type' => 'error'
            ]);
        }

        try {
            $user->update(['is_active' => false]);
            
            // Log the activity
            Activity::create([
                'action' => 'User Deactivated',
                'user_id' => $authUser->id,
            ]);

            return redirect()->back()->with([
                'message' => 'User deactivated successfully',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Failed to deactivate user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    public function user_activate(User $user)
    {
        $authUser = Auth::user();
        
        // Check if user is superadmin or IT Admin 
        if ($authUser->default_role !== 'superadmin' && $authUser->default_role !== 'IT Admin') {
            return redirect()->back()->with([
                'message' => 'Unauthorized action',
                'alert-type' => 'error'
            ]);
        }

        try {
            $user->update(['is_active' => true]);
            
            // Log the activity
            Activity::create([
                'action' => 'User Activated',
                'user_id' => $authUser->id,
            ]);

            return redirect()->back()->with([
                'message' => 'User activated successfully',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Failed to activate user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    /** Verify a user's account by setting email_verified_at */
    public function user_verify(User $user)
    {
        $authUser = Auth::user();

        // Only superadmin or IT Admin can verify
        if ($authUser->default_role !== 'superadmin' && $authUser->default_role !== 'IT Admin') {
            return redirect()->back()->with([
                'message' => 'Unauthorized action',
                'alert-type' => 'error'
            ]);
        }

        try {
            if (!is_null($user->email_verified_at)) {
                return redirect()->back()->with([
                    'message' => 'User is already verified',
                    'alert-type' => 'info'
                ]);
            }

            $user->update(['email_verified_at' => now()]);

            Activity::create([
                'action' => 'User Verified',
                'user_id' => $authUser->id,
            ]);

            return redirect()->back()->with([
                'message' => 'User account verified successfully',
                'alert-type' => 'success'
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message' => 'Failed to verify user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    public function showUserUploadForm()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.usermanager.uploadUser', compact('authUser', 'userTenant'));
    }


    public function userUploadCsv(Request $request)
    {
        // Validate the uploaded file
        // Note: tenant_name and department_name come from the CSV rows;
        // do not require them at the request-level.
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with([
                    'message' => 'Validation failed for CSV upload.',
                    'alert-type' => 'error'
                ])
                ->withInput();
        }

        // Get the uploaded file
        $file = $request->file('csv_file');

        // Read and process CSV
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csvData);

        $duplicates = [];
        $errors = [];
        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                try {
                    if (count($row) !== count($header)) {
                        $errors[] = "Row " . ($index + 1) . ": Column count mismatch";
                        continue;
                    }

                    $data = array_combine($header, $row);
                    // Normalize whitespace on all string fields
                    foreach ($data as $key => $val) {
                        if (is_string($val)) {
                            $data[$key] = (string) Str::of($val)->trim()->squish();
                        }
                    }

                    // Check for existing user
                    if (User::where('email', $data['email'])->exists()) {
                        $duplicates[] = $data['email'];
                        continue;
                    }

                    // Resolve organization (tenant) with case-insensitive match and normalized spacing
                    $tenantName = (string) Str::of($data['tenant_name'])->trim()->squish();
                    $tenant = Tenant::whereRaw('LOWER(name) = ?', [Str::lower($tenantName)])->first();
                    if (!$tenant) {
                        $errors[] = "Row " . ($index + 1) . ": Organization '{$data['tenant_name']}' not found. Please verify the name exactly matches a Tenant and ensure it is pre-created with required fields (code, email).";
                        continue;
                    }

                    // Resolve department
                    $departmentName = (string) Str::of($data['department_name'])->trim()->squish();
                    $department = TenantDepartment::where('tenant_id', $tenant->id)
                        ->whereRaw('LOWER(name) = ?', [Str::lower($departmentName)])
                        ->first();
                    if (!$department) {
                        $errors[] = "Row " . ($index + 1) . ": Department '{$data['department_name']}' not found in {$data['tenant_name']}. Create the department under the organization or correct the CSV name.";
                        continue;
                    }

                    // Create user
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'default_role' => $data['role'],
                        'email_verified_at' => now()
                    ]);

                    // Create user details
                    UserDetails::create([
                        'user_id' => $user->id,
                        'nin_number' => $data['nin'],
                        'gender' => $data['gender'],
                        'phone_number' => $data['phone'],
                        'tenant_id' => $tenant->id,
                        'designation' => $data['designation'],
                        'department_id' => $department->id,
                        'account_type' => $data['account_type'],
                        'state' => $data['state'],
                        'lga' => $data['lga'],
                        'country' => $data['country'],
                    ]);

                    // Assign role
                    $role = Role::where('name', $data['role'])->first();
                    if ($role) {
                        $user->assignRole($role);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            // Prepare notification
            $notification = [
                'message' => "Successfully created $successCount users",
                'alert-type' => 'success'
            ];

            if (!empty($duplicates)) {
                $notification['message'] .= ". Skipped " . count($duplicates) . " duplicates";
                $notification['alert-type'] = 'warning';
            }

            if (!empty($errors)) {
                $notification['message'] .= ". " . count($errors) . " errors occurred";
                // Use a distinct key to avoid clashing with Laravel's validation error bag
                $notification['row_errors'] = $errors;
                $notification['alert-type'] = 'error';
            }

            return redirect()->back()->with($notification);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'message' => 'Transaction failed: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }
    /**Role Management */
    public function roleIndex()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $roles = Role::all();
        return view('superadmin.roles.index', compact('authUser', 'userTenant', 'roles'));
    }
    public function roleCreate()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.roles.create', compact('authUser', 'userTenant'));
    }
    public function roleStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
        ]);
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);
        $notification = [
            'message' => 'Role created successfully',
            'type' => 'success',
        ];
        return redirect()->route('role.index')->with($notification);
    }


    /**Designation Management */
    public function designationIndex()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $designations = Designation::all();
        return view('superadmin.designations.index', compact('authUser', 'userTenant', 'designations'));
    }
    public function designationCreate()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.designations.create', compact('authUser', 'userTenant'));
    }
    public function designationStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $designation = Designation::create($request->all());
        $notification = [
            'message' => 'Designation created successfully',
            'type' => 'success',
        ];
        return redirect()->route('designation.index')->with($notification);
    }

    public function designationEdit(Designation $designation)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.designations.edit', compact('authUser', 'userTenant', 'designation'));
    }
    public function designationUpdate(Request $request, Designation $designation)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $designation->update($request->all());
        $notification = [
            'message' => 'Designation updated successfully',
            'type' => 'success',
        ];
        return redirect()->route('designation.index')->with($notification);
    }

    public function designationDestroy(Designation $designation)
    {
        $designation->delete();
        $notification = [
            'message' => 'Designation deleted successfully',
            'type' => 'sucess',
        ];
        return redirect()->route('designation.index')->with($notification);
    }

    /**Organisation Management */
    public function org_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $organisations = Tenant::orderBy('id', 'desc')->get();
            return view('superadmin.organisations.index', compact('organisations', 'authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function org_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.organisations.create', compact('authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function org_store(Request $request)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:tenants',
                'email' => 'required|string|email|max:255|unique:tenants',
                'phone' => 'nullable|string|max:255',
                'category' => 'required|string',
                'address' => 'nullable|string',
                'status' => 'required|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '.' . $logo->getClientOriginalExtension();
                $logo = $logo->move(public_path('logos/'), $logoName);
                $logoPath = $logo->getRealPath();
            } else {
                $logoName = null;
            }

            $tenant = new Tenant();
            $tenant->name = $request->input('name');
            $tenant->code = $request->input('code');
            $tenant->email = $request->input('email');
            $tenant->phone = $request->input('phone');
            $tenant->category = $request->input('category');
            $tenant->address = $request->input('address');
            $tenant->status = $request->input('status');
            $tenant->logo = $logoName;

            $tenant->save();
            $notification = [
                'message' => 'Organisation created successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('organisation.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function org_edit(Tenant $tenant)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            return view('superadmin.organisations.edit', compact('tenant', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function org_update(Request $request, Tenant $tenant)
    {
        $authUser  = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:tenants,code,' . $tenant->id,
                'email' => 'required|string|email|max:255|unique:tenants,email,' . $tenant->id,
                'phone' => 'nullable|string|max:255',
                'category' => 'required|string',
                'address' => 'nullable|string',
                'status' => 'required|string',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            ]);

            // Check if the tenant has an existing logo
            if ($tenant->logo) {
                // Get the path to the existing logo
                $existingLogoPath = public_path('logos/' . $tenant->logo);

                // Check if the existing logo exists
                if (file_exists($existingLogoPath)) {
                    // Delete the existing logo
                    unlink($existingLogoPath);
                }
            }

            $logoName = null;
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '.' . $logo->getClientOriginalExtension();
                $logo->move(public_path('logos/'), $logoName);
            }

            $tenant->name = $request->input('name');
            $tenant->code = $request->input('code');
            $tenant->email = $request->input('email');
            $tenant->phone = $request->input('phone');
            $tenant->category = $request->input('category');
            $tenant->address = $request->input('address');
            $tenant->status = $request->input('status');
            $tenant->logo = $logoName;
            $tenant->save();
            $notification = [
                'message' => 'Organisation updated successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('organisation.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function org_departments(Tenant $tenant)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $departments = TenantDepartment::where('tenant_id', $tenant->id)->get();
            return view('superadmin.departments.index', compact('departments', 'tenant', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function org_delete(Tenant $tenant)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $tenant->delete();
            $notification = [
                'message' => 'Organisation deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('organisation.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    /**Document Management */
    public function document_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $documents = DocumentStorage::myDocuments();

            return view('superadmin.documents.index', compact('documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            $documents = DocumentStorage::myDocuments();
            $tenantName = optional($authUser->userDetail)->tenant->name;
            return view('admin.documents.index', compact('documents', 'authUser', 'tenantName', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $documents = DocumentStorage::myDocuments();
            return view('secretary.documents.index', compact('documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            $documents = DocumentStorage::myDocuments();
            return view('user.documents.index', compact('documents', 'authUser', 'userTenant'));
        }
        if (in_array($authUser->default_role, ['Staff', 'IT Admin'])) {
            $documents = DocumentStorage::myDocuments();

            return view('staff.documents.index', compact('documents', 'authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function setCharge()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $fileCharges = FileCharge::where('status', 'active')->get();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.filecharge', compact('authUser', 'userTenant', 'fileCharges'));
        } else {
            return view('errors.404', compact('authUser', 'userTenant'));
        }
    }

    public function storeFileCharge(Request $request)
    {
        $request->validate([
            'file_charge' => 'required|numeric',
            'status' => 'required|string|in:active,inactive',
        ]);

        // Check if there is an active file charge
        $activeFileCharge = FileCharge::where('status', 'active')->first();

        if ($activeFileCharge) {
            // Notify the user that an active file charge exists
            $notification = [
                'message' => 'There is already an active file charge. The new charge has been set to inactive.',
                'alert-type' => 'warning'
            ];

            // Set the new charge status to inactive
            $request->merge(['status' => 'inactive']);
        } else {
            // Success message if no active charge exists
            $notification = [
                'message' => 'File Charge has been successfully created',
                'alert-type' => 'success'
            ];
        }

        // Create the new file charge
        FileCharge::create($request->all());

        return redirect()->back()->with($notification);
    }


    public function editFileCharge(FileCharge $fileCharge)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.edit_filecharge', compact('fileCharge', 'authUser', 'userTenant'));
        } else {
            return view('errors.404', compact('authUser', 'userTenant'));
        }
    }

    public function updateFileCharge(Request $request, FileCharge $fileCharge)
    {
        $request->validate([
            'file_charge' => 'required|numeric',
            'status' => 'required|string|in:active,inactive',
        ]);

        $fileCharge->update($request->all());

        $notification = [
            'message' => 'File Charge has been successfully updated',
            'alert-type' => 'success'
        ];
        return redirect()->route('set.charge')->with($notification);
    }

    public function deleteFileCharge(FileCharge $fileCharge)
    {
        $fileCharge->delete();
        $notification = [
            'message' => 'File Charge has been successfully deleted',
            'alert-type' => 'success'
        ];
        return redirect()->route('set.charge')->with($notification);
    }

    public function document_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.create', compact('authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            return view('admin.documents.create', compact('authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            return view('admin.documents.create', compact('authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            return view('user.documents.create', compact('authUser', 'userTenant'));
        }
        if (in_array($authUser->default_role, ['Staff', 'IT Admin'])) {
            return view('staff.documents.create', compact('authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function user_file_document()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'User') {
            $recipients = DocumentStorage::getUserRecipients();

            return view('user.documents.filedocument', compact('recipients', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Free filing for users */
    // public function user_store_file_document(Request $request)
    // {
    //     $authUser = Auth::user();
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'document_number' => 'required|string|max:255',
    //         'file_path' => 'required|mimes:pdf|max:2048', // PDF file, max 2MB
    //         'uploaded_by' => 'required|exists:users,id',
    //         'status' => 'nullable|in:pending,processing,approved,rejected,kiv,completed',
    //         'description' => 'nullable|string',
    //         'recipient_id' => 'required|exists:users,id',
    //         'metadata' => 'nullable|json',
    //     ]);
    //     if ($request->hasFile('file_path')) {
    //         $uploadedBy = $request->input('uploaded_by');
    //         $filePath = $request->file('file_path');
    //         $filename = time() . '_' . $filePath->getClientOriginalName();
    //         $file_path = $filePath->storeAs('documents/users/'. $uploadedBy, $filename, 'public');
    //         $file = $request->merge(['file_path' => $filename]);
    //     }

    //     $document = Document::create([
    //         'title' => $request->title,
    //         'docuent_number' => $request->document_number,
    //         'file_path' => 'documents/users/' . $uploadedBy . '/' . $filename,
    //         'uploaded_by' => $request->uploaded_by,
    //         'status' => 'pending',
    //         'description' => $request->description,
    //         'metadata' => json_encode($request->metadata),
    //     ]);

    //     // Log document upload activity
    //     Activity::create([
    //         'action' => 'You uploaded a document',
    //         'user_id' => Auth::id(),
    //     ]);

    //     // Create file movement record
    //     $fileMovement = FileMovement::create([
    //         'recipient_id' => $request->recipient_id,
    //         'sender_id' => Auth::id(),
    //         'message' => $request->description,
    //         'document_id' => $document->id,
    //     ]);

    //     // Create document recipient record
    //     DocumentRecipient::create([
    //         'file_movement_id' => $fileMovement->id,
    //         'recipient_id' => $request->recipient_id,
    //         'user_id' => Auth::id(),
    //         'created_at' => now(),
    //     ]);

    //     // Log additional activities
    //     Activity::insert([
    //         [
    //             'action' => 'Sent Document',
    //             'user_id' => Auth::id(),
    //             'created_at' => now(),
    //         ],
    //         [
    //             'action' => 'Document Received',
    //             'user_id' => $request->recipient_id,
    //             'created_at' => now(),
    //         ],
    //     ]);

    //     $senderName = Auth::user()->name;
    //     $receiverName = User::find($request->recipient_id)->name;
    //     $documentName = $request->title;
    //     $documentId = $request->docuent_number;
    //     $appName = config('app.name');

    //     try {
    //         Mail::to(Auth::user()->email)->send(new SendNotificationMail($senderName, $receiverName,  $documentName, $appName));
    //         Mail::to(User::find($request->recipient_id)?->email)->send(new ReceiveNotificationMail($senderName, $receiverName, $documentName, $documentId, $appName));
    //     } catch (\Exception $e) {
    //         Log::error('Failed to send Document notification');
    //     }
    //     Log::alert('Document uploaded and sent by'. $authUser->name);
    //     // Redirect with success notification
    //     return $this->redirectWithNotification('Document uploaded and sent successfully.', 'success');
    // }

    /**Paid filing */

    public function user_store_file_document(Request $request)
    {
       
        $request->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'file_path' => 'required|mimes:pdf|max:10240', // PDF file, max 2MB
            'uploaded_by' => 'required|exists:users,id',
            'status' => 'nullable|in:pending,processing,approved,rejected,kiv,completed',
            'description' => 'nullable|string',
            'recipient_id' => 'required|exists:users,id',
            'metadata' => 'nullable|json',
        ]);

        if ($request->hasFile('file_path')) {
            $uploadedBy = $request->input('uploaded_by');
            $tenantId = Auth::user()->userDetail->tenant_id;
            $filePath = $request->file('file_path');
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($filePath->getPathname());
            // Defer Cloudinary upload until after successful payment: store temp file locally
            $filename = time() . '_' . $filePath->getClientOriginalName();
            $relativeTempPath = 'temp_uploads/' . $tenantId . '/' . $uploadedBy;
            try {
                Storage::disk('local')->makeDirectory($relativeTempPath);
                // Use UploadedFile::storeAs to save into local disk
                $filePath->storeAs($relativeTempPath, $filename, 'local');
            } catch (Exception $e) {
                Log::error('Temporary file storage error: ' . $e->getMessage());
                return redirect()->back()->with([
                    'message' => 'Failed to store file temporarily: ' . $e->getMessage(),
                    'alert-type' => 'error',
                ]);
            }
        }

        $reference = Str::random(12);
        $filingCharge = FileCharge::where('status', 'active')->first('file_charge');

        $charge = $pageCount * $filingCharge->file_charge;
        $amount = $charge;
        $documentHold = DocumentHold::create([
            'title' => $request->title,
            'docuent_number' => $request->document_number,
            // Store relative temp path; upload to Cloudinary on successful payment
            'file_path' => $relativeTempPath . '/' . $filename,
            'uploaded_by' => Auth::user()->id,
            'status' => $request->status ?? 'pending',
            'description' => $request->description,
            'reference' => $reference,
            'amount' => $amount,
            'recipient_id' => $request->recipient_id,
            'metadata' => json_encode($request->metadata),
        ]);


        $authUser = Auth::user();

        try {

            $payload = [
                "email" => $authUser->email,
                "amount" => ($amount * 100),
                "reference" => $reference,
                "callbackUrl" => route("etranzact.callBack"),
                "bearer" => 0,
            ];

            // Attempt 1: Public key without Bearer (per Credo docs examples)
            $response = Http::accept('application/json')
                ->withHeaders([
                    'Authorization' => env('CREDO_PUBLIC_KEY'),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(15)
                ->retry(2, 1000)
                ->post(env("CREDO_URL") . "/transaction/initialize", $payload);

            if (!$response->successful()) {
                Log::warning('Credo init attempt1 failed: status=' . $response->status() . ' body=' . $response->body());
                // Attempt 2: Public key with Bearer prefix (some environments may expect this)
                $response = Http::accept('application/json')
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . env('CREDO_PUBLIC_KEY'),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->timeout(15)
                    ->retry(2, 1000)
                    ->post(env("CREDO_URL") . "/transaction/initialize", $payload);
            }

            if (!$response->successful()) {
                Log::error('Credo init failed: status=' . $response->status() . ' body=' . $response->body());
                return redirect()->back()->with([
                    'message' => 'Payment gateway rejected credentials. Please verify CREDO_URL and keys.',
                    'alert-type' => 'error',
                ]);
            }

            $responseData = $response->json("data");

            if (is_array($responseData) && isset($responseData['authorizationUrl'])) {
                return redirect($responseData['authorizationUrl']);
            }

            $notification = [
                'message' => 'Credo E-Tranzact gateway service took too long to respond.',
                'alert-type' => 'error',
            ];
            Log::error('Credo init missing authorizationUrl: ' . json_encode($response->json()));

            return redirect()->back()->with($notification);
        } catch (Exception $e) {
            report($e);
            Log::error('Error initializing payment gateway: ' . $e->getMessage());
            $notification = [
                'message' => 'Error initializing payment gateway. Please try again.',
                'alert-type' => 'error',
            ];
            return redirect()->back()->with($notification);
        }
    }

  
    public function handleETranzactCallback(Request $request)
    {
        // Extract reference from common callback parameters
        $reference = $request->input('reference')
            ?? $request->input('ref')
            ?? $request->input('transaction_reference')
            ?? null;

        if (!$reference) {
            Log::error('Credo callback missing reference parameter', ['query' => $request->query()]);
            return $this->handleFailedPayment('Missing transaction reference in callback.');
        }

        // Verify the transaction with the payment gateway
        $response = Http::accept('application/json')
            ->withHeaders([
                // Per Credo docs, verification uses SECRET KEY (no Bearer)
                'Authorization' => env('CREDO_SECRET_KEY'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->retry(2, 1000)
            ->get(env('CREDO_URL') . "/transaction/{$reference}/verify");

        // Check if the response is successful
        if (!$response->successful()) {
            Log::error('Credo verification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'reference' => $reference,
            ]);
            return $this->handleFailedPayment('Payment verification failed. Please try again.');
        }

        $payment = $response->json('data');

        // Extract payment status and message
        $status = $payment['status'];
        $message = $payment['statusMessage'] == 'Successfully processed' ? 'Successful' : 'Failed';

        // Handle successful payment
        if ($message == 'Successful') {
            $recipient_id = DocumentHold::where('reference', $reference)->first()->recipient_id;
            $document_no = DocumentHold::where('reference', $reference)->first()->docuent_number;
            $tenant_id = User::with('userDetail')->where('id', $recipient_id)->first()->userDetail->tenant_id;

            // Create a new payment record
            Payment::create([
                'businessName' => $payment['businessName'],
                'document_no' => $document_no,
                'reference' => $payment['businessRef'] ?? $reference,
                'transAmount' => $payment['transAmount'],
                'transFee' => $payment['transFeeAmount'],
                'transTotal' => $payment['debitedAmount'],
                'transDate' => $payment['transactionDate'],
                'settlementAmount' => $payment['settlementAmount'],
                'status' => $payment['status'],
                'statusMessage' => $payment['statusMessage'],
                'customerEmail' => $payment['customerId'],
                'customerId' => Auth::id(),
                'channelId' => $payment['channelId'],
                'currencyCode' => $payment['currencyCode'],
                'recipient_id' => $recipient_id,
                'tenant_id' => $tenant_id,
            ]);
            return $this->handleSuccessfulPayment($reference);
        }

        // Handle failed payment
        return $this->handleFailedPayment('Payment failed. Please try again.');
    }

    /**
     * Handle a successful payment.
     */
    protected function handleSuccessfulPayment($reference)
    {
        // Find the document hold record
        $document = DocumentHold::where('reference', $reference)->first();

        if (!$document) {
            return $this->handleFailedPayment('Document not found.');
        }

        // Update document hold status
        $document->status = 'Successful';
        $document->save();

        // If file_path is a local temp path, upload to Cloudinary now
        $finalFileUrl = $document->file_path;
        try {
            if (!preg_match('#^https?://#', $document->file_path)) {
                $uploader = new CloudinaryHelper();
                $uploaderUser = User::with('userDetail')->find($document->uploaded_by);
                $tenantId = $uploaderUser?->userDetail?->tenant_id;
                $folder = 'edms_documents/' . ($tenantId ?? 'tenant') . '/' . $document->uploaded_by;

                $absolutePath = storage_path('app/' . ltrim($document->file_path, '/'));
                $uploadResult = $uploader->upload($absolutePath, $folder);
                if (is_array($uploadResult) && isset($uploadResult['secure_url'])) {
                    $finalFileUrl = $uploadResult['secure_url'];
                }

                // Cleanup temp file
                @unlink($absolutePath);
            }
        } catch (Exception $e) {
            Log::error('Cloudinary upload during payment callback failed: ' . $e->getMessage());
            // Proceed with existing file_path; user can retry upload later.
        }

        // Create a new document
        $newDocument = Document::create([
            'title' => $document->title,
            'docuent_number' => $document->docuent_number,
            'file_path' => $finalFileUrl,
            'uploaded_by' => $document->uploaded_by,
            'status' => 'pending',
            'description' => $document->description,
            // 'metadata' => json_encode($document->metadata),
        ]);

        // Log document upload activity
        Activity::create([
            'action' => 'You uploaded a document',
            'user_id' => Auth::id(),
        ]);

        // Create file movement record
        $fileMovement = FileMovement::create([
            'recipient_id' => $document->recipient_id,
            'sender_id' => Auth::id(),
            'message' => $document->description,
            'document_id' => $newDocument->id,
        ]);

        // Create document recipient record
        DocumentRecipient::create([
            'file_movement_id' => $fileMovement->id,
            'recipient_id' => $document->recipient_id,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        // Log additional activities
        Activity::insert([
            [
                'action' => 'Sent Document',
                'user_id' => Auth::id(),
                'created_at' => now(),
            ],
            [
                'action' => 'Document Received',
                'user_id' => $document->recipient_id,
                'created_at' => now(),
            ],
        ]);
        $userOrg = User::with('userDetail.tenant')->where('id', $document->recipient_id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $document->recipient_id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;

        $senderName = Auth::user()->name;
        $receiverName = User::find($document->recipient_id)->name;
        $documentName = $document->title;
        $documentId = $document->docuent_number;
        $appName = config('app.name');

        try {
            Mail::to(Auth::user()->email)->send(new SendNotificationMail($senderName, $receiverName,  $documentName, $appName, $userTenant, $userDepartment));
            Mail::to(User::find($document->recipient_id)?->email)->send(new ReceiveNotificationMail($senderName, $receiverName, $documentName, $documentId, $appName));
        } catch (\Exception $e) {
            Log::error('Failed to send Document notification');
        }

        // Redirect with success notification
        return $this->redirectWithNotification('Document uploaded and sent successfully.', 'success');
    }

    // /**
    //  * Handle a failed payment.
    //  */
    protected function handleFailedPayment($message)
    {
        return $this->redirectWithNotification($message, 'error');
    }

    /**
     * Redirect with a notification.
     */
    protected function redirectWithNotification($message, $type)
    {
        $notification = [
            'message' => $message,
            'alert-type' => $type,
        ];

        return redirect()->route('document.index')->with($notification);
    }

    public function myDocument_show(Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            return view('admin.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            return view('secretary.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            return view('user.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            return view('staff.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Show a document to the user */
    public function document_show($received, Document $document)
    {
        $authUser = Auth::user();
        $tenantId = $authUser->userDetail->tenant_id ?? null;
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $received)->first();
            
            return view('superadmin.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->orderBy('updated_at', 'desc')->get();
           
            return view('admin.documents.show', compact('document_received', 'document_locations', 'authUser', 'userTenant'));
           
        }
        if (Auth::user()->default_role === 'Secretary') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
           
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->orderBy('updated_at', 'desc')->get();

            return view('secretary.documents.show', compact('document_received', 'document_locations', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();

            return view('user.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();

            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->orderBy('updated_at', 'desc')->get();

            return view('staff.documents.show', compact('document_received', 'document_locations', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function document_show_sent($sent)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            
            return view('superadmin.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('admin.documents.show', compact('document_received', 'authUser', 'userTenant', 'recipients', 'notification'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('admin.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('user.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('staff.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function document_store(Request $request)
    {

        $data = $request;
        $result = DocumentStorage::storeDocument($data);

        if ($result['status'] === 'error') {
            return redirect()->back()
                ->withErrors($result['errors'])
                ->withInput();
        }
        $notification = array(
            'message' => 'Document uploaded successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('document.index')->with($notification);
    }

    public function sent_documents()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('superadmin.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            // dd($sent_documents);
            return view('admin.documents.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            return view('secretary.documents.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {

            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            // Check if $recipient is null or empty
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('user.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with(['tenant', 'tenant_department'])->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('staff.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function received_documents()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('superadmin.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('admin.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return view('secretary.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('user.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('staff.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function viewDocument(Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $tenantId = $document->tenant_id;
        $departmentId = $document->department_id;
        $filePath = $document->file_path;

        $file = storage_path('documents/' . $tenantId . '/' . $departmentId . '/' . $filePath);

        if (file_exists($file)) {
            return response()->file($file, [
                'Content-Disposition' => 'inline; filename="' . basename($file) . '"',
                'Content-Type' => 'application/pdf', // Paul-ben, Change this based on your file type
            ]);
        }

        abort(404);
    }

    public function getReplyform(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $authUser = Auth::user();

            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();


            return view('staff.documents.reply', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $authUser = Auth::user();

            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();


            return view('staff.documents.reply', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            $authUser = Auth::user();

            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();


            return view('staff.documents.reply', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function getSendExternalForm(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $recipients = User::with(['userDetail.tenant' => function ($query) {
                $query->select('id', 'name'); // Include only relevant columns
            }])
                ->where('default_role', 'Admin') // Admins in other tenants
                ->get();

            if ($recipients->isEmpty()) {
                $notification = [
                    'message' => 'No recipients found.',
                    'alert-type' => 'error'
                ];
                return redirect()->back()->with($notification);
            }
            return view('admin.documents.send_external', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        $notification = [
            'message' => 'You do not have permission to send external documents.',
            'alert-type' => 'error'
        ];
        return view('errors.404', compact('authUser', 'userTenant'))->with($notification);
    }

    public function getSendform(Request $request, Document $document)
    {

        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $role = $authUser->default_role;

        switch ($role) {
            case 'superadmin':
                $recipients = User::all();
                $notification = [
                    'message' => 'Messages sent are end to end encrypted.',
                    'alert-type' => 'info'
                ];
                return view('superadmin.documents.send', compact('recipients', 'document', 'authUser'))->with($notification);

            case 'Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }

                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    $notification = [
                        'message' => 'No recipients found.',
                        'alert-type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                $notification = [
                        'message' => 'Messages are end to end encrypted.',
                        'alert-type' => 'info',
                    ];

                return view('admin.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'))->with($notification);

            case 'User':
                $recipients = User::where('default_role', 'Admin')->get();
                return view('user.documents.send', compact('recipients', 'document', 'authUser', 'userTenant'));

            case 'Staff':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));
            case 'IT Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));
            case 'Secretary':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }

                $recipients = User::with(['userDetail' => function ($query) {
                    $query->select('id', 'user_id', 'designation', 'tenant_id');
                }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();


                if ($recipients->isEmpty()) {
                    $notification = [
                        'title' => 'No recipients found.',
                        'message' => 'No recipients found.',
                        'type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('secretary.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));


            default:
                return view('errors.404', compact('authUser'));
        }
    }

    public function sendDocument(Request $request)
    {
        $data = $request;
        $userOrg = User::with('userDetail.tenant')->where('id', $data->recipient_id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $data->recipient_id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;
        $document = Document::where('id', $data->document_id)->first()->docuent_number ?? null;

        $result = DocumentStorage::sendDocument($data);
        if ($result['status'] === 'error') {
            return redirect()->back()
                ->withErrors($result['errors'])
                ->withInput();
        }
        try {
            SendMailHelper::sendNotificationMail($data, $request, $userDepartment, $userTenant);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return redirect()->route('document.index')->with([
                'message' => 'Document was processed, but notification email failed.',
                'alert-type' => 'warning',
            ]);
        }

        $notification = array(
            'message' => 'Document sent successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('document.sent')->with($notification);
    }

    public function secSendToAdmin(Request $request, Document $document)
    {

        // Validate request input
        $validated = $request->validate([
            'document_data' => 'required|json',
        ]);

        // Decode the JSON data
        $documentData = json_decode($validated['document_data'], true);

        $documentID = $documentData['document_id'];
        // Validate required fields in the decoded data
        if (!isset($documentData['document']['id'], $documentData['sender']['name'], $documentData['recipient']['name'])) {
            return redirect()->back()->with([
                'message' => 'Invalid document data provided.',
                'alert-type' => 'error',
            ]);
        }

        // Retrieve tenant and role details
        $authUser = Auth::user();
        $tenantId = $authUser->userDetail->tenant_id ?? null;

        // Check for tenant assignment
        if (!$tenantId) {
            return redirect()->back()->with([
                'message' => 'You are not assigned to any tenant.',
                'alert-type' => 'error',
            ]);
        }

        // Fetch the recipient(s)
        $recipient = User::with('userDetail')
            ->where('default_role', 'Admin')
            ->whereHas('userDetail', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->first();
        // dd($recipient);

        if ($recipient === null) {
            return redirect()->back()->with([
                'message' => 'No admin users found for the tenant.',
                'alert-type' => 'error',
            ]);
        }

        // ===== NEW: Check for existing submission =====
        // $existingSubmission = FileMovement::where([
        //     'document_id' => $documentID,
        //     'recipient_id' => $recipient->id,
        // ])->where('sender_id', '!=',  $authUser->id)->exists();

        // if ($existingSubmission) {
        //     return redirect()->back()->with([
        //         'message' => 'This document has already been sent to admin.',
        //         'alert-type' => 'error',
        //     ]);
        // }
        // ===== END OF NEW CHECK =====

        // Process the document
        $stamp = StampHelper::stampIncomingMail($documentID);
        $result = DocumentStorage::reviewedDocument($documentData, $recipient);

        $userOrg = User::with('userDetail.tenant')->where('id', $recipient->id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $recipient->id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;

        // Send notification email
        try {
            SendMailHelper::sendReviewNotificationMail($documentData, $recipient, $userTenant, $userDepartment);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Document was processed, but notification email failed.',
                'alert-type' => 'warning',
            ]);
        }

        // Redirect with success notification
        return redirect()->route('document.index')->with([
            'message' => 'Document sent successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function track_document(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (in_array(Auth::user()->default_role, ['Admin', 'Staff', 'Secretary', 'IT Admin'])) {
            // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant.tenant_departments'])->where('document_id', $document->id)->get();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

            return view('staff.documents.filemovement', compact('document_locations', 'document', 'authUser', 'userTenant'));
        }

        if (Auth::user()->default_role === 'User') {
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
            return view('user.documents.filemovement', compact('document_locations', 'document', 'authUser', 'userTenant'));
        }
    }

    public function get_attachments(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return view('admin.documents.attachments', compact('attachments', 'document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return view('secretary.documents.attachments', compact('attachments', 'document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Staff') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return view('staff.documents.attachments', compact('attachments', 'document', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Memo Actions */
    public function memo_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $memos = Memo::where('user_id', $authUser->id)->orderBy('id', 'desc')->get();
        return view('admin.memo.index', compact('memos', 'authUser', 'userTenant'));
    }

    public function create_memo()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('admin.memo.create', compact('authUser', 'userTenant'));
    }

    public function store_memo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255|unique:memos,docuent_number',
            'content' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'sender' => 'required|string',
            'receiver' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $memo = new Memo();
        $memo->title = $request->input('title');
        $memo->docuent_number = $request->input('document_number');
        $memo->content = $request->input('content');
        $memo->user_id = $request->input('user_id');
        $memo->sender = $request->input('sender');
        $memo->receiver = $request->input('receiver');
        $memo->save();

        $notification = array(
            'message' => 'Memo created successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.index')->with($notification);
    }

    public function edit_memo(Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('admin.memo.edit', compact('memo', 'authUser', 'userTenant'));
    }

    public function update_memo(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'content' => 'required|string',
            'sender' => 'required|string',
            'receiver' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $memo->title = $request->input('title');
        $memo->docuent_number = $request->input('document_number');
        $memo->content = $request->input('content');
        $memo->sender = $request->input('sender');
        $memo->receiver = $request->input('receiver');
        // $memo->user_id = $request->input('user_id');
        $memo->save();

        $notification = array(
            'message' => 'Memo updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.index')->with($notification);
    }

    public function delete_memo(Memo $memo)
    {
        $authUser = Auth::user();
        $memo->delete();
        $notification = array(
            'message' => 'Memo deleted successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.index')->with($notification);
    }



    public function generateMemoPdf(Memo $memo)
    {
        // Get the sender user (assuming sender is a user name or ID)
        $senderUser = User::where('name', $memo->sender)->with('userDetail')->first();

        $pdf = new Fpdi();
        /** @var \setasign\Fpdi\Fpdi $pdf */

        // Set dimensions from the letterhead template
        $templatePath = public_path('templates/letterhead.pdf');
        $pageCount = $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($template);

        // === Add logo at the top center ===
        $logoPath = null;
        $logoWidth = 20; // Adjust as needed
        $logoHeight = 20; // Adjust as needed

        // if (
        //     $senderUser &&
        //     $senderUser->userDetail &&
        //     $senderUser->userDetail->tenant &&
        //     $senderUser->userDetail->tenant->logo
        // ) {
        //     $logoPath = public_path('logos/' . $senderUser->userDetail->tenant->logo);
        //     if (file_exists($logoPath)) {
        //         // Get page width to center the logo
        //         $pageWidth = $pdf->GetPageWidth();
        //         $x = ($pageWidth - $logoWidth) / 2;
        //         $y = 15; // Top margin
        //         $pdf->Image($logoPath, $x, $y, $logoWidth, $logoHeight);
        //     }
        // }
        if (
            $senderUser &&
            $senderUser->userDetail &&
            $senderUser->userDetail->tenant &&
            $senderUser->userDetail->tenant->logo
        ) {
            $logoRelativePath = 'logos/' . $senderUser->userDetail->tenant->logo;
            $logoPath = public_path($logoRelativePath);

            if (file_exists($logoPath)) {
                $pageWidth = $pdf->GetPageWidth();
                $x = ($pageWidth - $logoWidth) / 2;
                $y = 15;
                try {
                    $pdf->Image($logoPath, $x, $y, $logoWidth, $logoHeight);
                } catch (\Exception $e) {
                    Log::error('Failed to load logo in PDF: ' . $e->getMessage());
                }
            } else {
                Log::warning("Logo file not found: {$logoPath}");
            }
        }


        // Set font
        $pdf->SetFont('Arial', '', 12);

        // Header positions (adjust these based on your letterhead template)
        $pdf->SetXY(35, 53);  // Sender position
        $pdf->Write(0, $memo->sender);

        $pdf->SetXY(35, 69);  // Subject position
        $pdf->Write(0, $memo->title);

        $pdf->SetXY(140, 53); // Recipient position
        $pdf->Write(0, $memo->receiver);

        $pdf->SetXY(140, 69); // Date position
        $pdf->Write(0, ($memo->created_at instanceof \Carbon\Carbon)
            ? $memo->created_at->format('M j, Y')
            : \Carbon\Carbon::parse($memo->created_at)->format('M j, Y')
        );

        // Content positioning
        $contentStartY = 85; // Starting Y position for main content

        // Salutation
        $pdf->SetXY(30, $contentStartY);
        $pdf->Write(0, 'Dear Sir/Madam,');

        // Main content with MultiCell for automatic line breaks
        $pdf->SetXY(30, $contentStartY + 15);
        $pdf->MultiCell(150, 6, $memo->content); // Width 150mm, height 6mm per line

        // Get Y position after content
        $yAfterContent = $pdf->GetY();

        // Closing section - 3 line spaces after content
        $lineHeight = 6; // Same as MultiCell line height
        $closingY = $yAfterContent + (3 * $lineHeight);

        // If closing would go past page bottom, add new page
        if ($closingY > ($pdf->getPageHeight() - 30)) {
            $pdf->AddPage();
            $pdf->useTemplate($template);
            $closingY = 50; // Reset Y position on new page
        }

        // Closing content
        $pdf->SetXY(30, $closingY);
        $pdf->Write(0, 'Yours faithfully,');

        // Signature (image or text) for the sender
        $signatureY = $closingY + 10;
        $signaturePath = $senderUser && $senderUser->id
            ? storage_path('app/signatures/' . $senderUser->id . '.png')
            : null;

        if ($signaturePath && file_exists($signaturePath)) {
            $pdf->Image($signaturePath, 50, $signatureY, 40, 15);
        } else {
            $pdf->SetXY(30, $signatureY);
            $pdf->Write(0, $senderUser->userDetail->signature ?? '');
        }

        // Name and designation of the sender
        $pdf->SetXY(30, $signatureY + 10);
        $pdf->Write(0, $senderUser->name ?? $memo['sender']);

        $pdf->SetXY(30, $signatureY + 16);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Write(0, $senderUser->userDetail->designation ?? '');


        // === Append Memo Movements Section ===
        // Fetch all memo movements for this memo using Eloquent for typed models
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\MemoMovement> $movements */
        $movements = MemoMovement::where('memo_id', $memo->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // if ($movements->count() > 0) {
        //     // Add a section title
        //     $pdf->SetFont('Arial', 'B', 12);
        //     $pdf->SetXY(30, $pdf->GetY() + 15);
        //     $pdf->Write(0, '--- Memo Minuting History ---');
        //     $pdf->SetFont('Arial', '', 11);

        //     foreach ($movements as $movement) {
        //         // Get sender details
        //         $sender = \App\Models\User::with('userDetail')->find($movement->sender_id);
        //         $senderName = $sender ? $sender->name : 'Unknown';
        //         $senderDesignation = $sender && $sender->userDetail ? $sender->userDetail->designation : '';

        //         // Format date
        //         $date = \Carbon\Carbon::parse($movement->created_at)->format('M j, Y g:i A');

        //         // Prepare message block
        //         $messageBlock = "Message: {$movement->message}\nBy: {$senderName} ({$senderDesignation})\nOn: {$date}\n";

        //         // Add some spacing before each message
        //         $pdf->SetXY(30, $pdf->GetY() + 7);
        //         $pdf->MultiCell(150, 6, $messageBlock, 0, 'L');
        //     }
        // }
        if ($movements->count() > 0) {
            // Add a section title
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetXY(30, $pdf->GetY() + 15);
            $pdf->Write(0, '--- Memo Minuting History ---');
            $pdf->SetFont('Arial', '', 11);

            foreach ($movements as $movement) {
                /** @var \App\Models\MemoMovement $movement */
                // Get sender details
                $sender = \App\Models\User::with('userDetail')->find($movement->sender_id);
                $senderName = $sender ? $sender->name : 'Unknown';
                $senderDesignation = $sender && $sender->userDetail ? $sender->userDetail->designation : '';

                // Get recipient details
                $recipient = \App\Models\User::with('userDetail')->find($movement->recipient_id);
                $recipientName = $recipient ? $recipient->name : 'Unknown';
                $recipientDesignation = $recipient && $recipient->userDetail ? $recipient->userDetail->designation : '';

                // Format date
                $date = \Carbon\Carbon::parse($movement->created_at)->format('M j, Y g:i A');

                // Prepare message block with sender and recipient details
                $messageBlock = "Message: {$movement->message}\n"
                    . "By: {$senderName} ({$senderDesignation})\n"
                    . "To: {$recipientName} ({$recipientDesignation})\n"
                    . "On: {$date}\n";

                // Add some spacing before each message
                $pdf->SetXY(30, $pdf->GetY() + 7);
                $pdf->MultiCell(150, 6, $messageBlock, 0, 'L');
            }
        }

        // Output the PDF
        return response()->stream(function () use ($pdf) {
            $pdf->Output('I', 'memo.pdf');
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="memo.pdf"',
        ]);
    }


    public function get_memo(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $senderUsers = User::where('name', $memo['sender'])->with('userDetail')->first();
        $senderUser = User::where('id', $memo['user_id'])->with('userDetail')->first();
        // dd($senderUser);
        $receiverUser = User::where('name', $memo['receiver'])->with('userDetail')->first();

        return view('admin.memo.show', compact('memo', 'authUser', 'userTenant', 'senderUser'));
    }

    public function createMemoTemplateForm()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('admin.memo.template', compact('authUser', 'userTenant'));
    }

    public function storeMemoTemplate(Request $request)
    {
        $authUser = Auth::user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:memo_templates,name',
            'template' => 'required|file|mimes:pdf,doc,docx|max:2048', // Allow PDF, Word files, max 2MB
            'user_id' => 'required|exists:users,id',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            $notification = [
                'message' => 'File upload failed',
                'alert-type' => 'error',
            ];
            return redirect()->back()->with($notification);
        }

        // Handle file upload
        if ($request->hasFile('template')) {
            $file = $request->file('template');
            $fileName = time() . '_' . $file->getClientOriginalName(); // Unique file name
            $filePath = $file->move(public_path('templates/'), $fileName); // Store in public/memo_templates

            // Create the memo template
            $memoTemplate = MemoTemplate::create([
                'name' => $request->name,
                'template' => $fileName, // Store the file name in the database
                'user_id' => $request->user_id,
            ]);

            $notification = [
                'message' => 'Memo template created successfully',
                'alert-type' => 'success',
            ];
            return redirect()->route('memo.index')->with($notification);
        }
        // $notification = [
        //     'message' => 'File upload failed',
        //     'alert-type' => 'error',
        // ];
        // return redirect()->back()->with($notification);
    }

    public function getSendMemoExternalForm(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $recipients = User::with(['userDetail.tenant' => function ($query) {
                $query->select('id', 'name'); // Include only relevant columns
            }])
                ->where('default_role', 'Admin') // Admins in other tenants
                ->get();

            if ($recipients->isEmpty()) {
                $notification = [
                    'message' => 'No recipients found.',
                    'alert-type' => 'error'
                ];
                return redirect()->back()->with($notification);
            }
            return view('admin.memo.send_external', compact('recipients', 'memo', 'authUser', 'userTenant'));
        }
        $notification = [
            'message' => 'You do not have permission to send external documents.',
            'alert-type' => 'error'
        ];
        return view('errors.404', compact('authUser', 'userTenant'))->with($notification);
    }

    public function getSendMemoform(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $role = $authUser->default_role;

        switch ($role) {
            case 'superadmin':
                $recipients = User::all();
                return view('admin.memo.send', compact('recipients', 'document', 'authUser', 'userTenant'));

            case 'Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    $notification = [
                        'message' => 'Tenant information is missing.',
                        'alert-type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    $notification = [
                        'message' => 'No recipients found.',
                        'alert-type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('admin.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));

            case 'User':
                $recipients = User::where('default_role', 'Admin')->get();
                return view('user.documents.send', compact('recipients', 'document', 'authUser', 'userTenant'));

            case 'Staff':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));
            case 'IT Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));
            case 'Secretary':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }

                $recipients = User::with(['userDetail' => function ($query) {
                    $query->select('id', 'user_id', 'designation', 'tenant_id');
                }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();


                if ($recipients->isEmpty()) {
                    $notification = [
                        'title' => 'No recipients found.',
                        'message' => 'No recipients found.',
                        'type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('staff.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));


            default:
                return view('errors.404', compact('authUser', 'userTenant'));
        }
    }

    public function sendMemo(Request $request)
    {
        $data = $request;
        $userOrg = User::with('userDetail.tenant')->where('id', $data->recipient_id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $data->recipient_id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;
        $result = DocumentStorage::sendMemo($data);
        if ($result['status'] === 'error') {
            return redirect()->back()
                ->withErrors($result['errors'])
                ->withInput();
        }
        try {
            SendMailHelper::sendNotificationMail($data, $request, $userTenant, $userDepartment);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return redirect()->route('document.index')->with([
                'message' => 'Document was processed, but notification email failed.',
                'alert-type' => 'warning',
            ]);
        }

        $notification = array(
            'message' => 'Memo sent successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.sent')->with($notification);
    }


    public function sent_memos()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('admin.memo.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            // dd($sent_documents);
            return view('admin.memo.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            return view('secretary.memo.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {

            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            // Check if $recipient is null or empty
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('user.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with(['tenant', 'tenant_department'])->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('staff.memo.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function received_memos()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('admin.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($received_documents) = DocumentStorage::getReceivedMemos();
            // dd($received_documents);
            return view('admin.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($received_documents) = DocumentStorage::getReceivedMemos();

            return view('secretary.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('user.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($received_documents) = DocumentStorage::getReceivedMemos();

            return view('staff.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Department Management */
    public function department_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $departments = TenantDepartment::orderBy('id', 'desc')->get();
            return view('superadmin.departments.index', compact('departments', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            // Retrieve the tenant_id of the authenticated user
            $tenantId = Auth::user()->userdetail->tenant_id;

            // Filter TenantDepartment by tenant_id and paginate the results
            $departments = TenantDepartment::where('tenant_id', $tenantId)
                ->orderBy('id', 'desc')
                ->get();
            return view('admin.departments.index', compact('departments', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function department_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $organisations = Tenant::all();
            return view('superadmin.departments.create', compact('organisations', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            // Retrieve the tenant_id of the authenticated user
            $tenantId = Auth::user()->userdetail->tenant_id;
            $organisations = Tenant::where('id', $tenantId)->first();
            return view('admin.departments.create', compact('organisations', 'tenantId', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function department_store(Request $request)
    {
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department = TenantDepartment::create($request->all());

            return redirect()->route('department.index')->with('success', 'Department created successfully.');
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department = TenantDepartment::create($request->all());
            return redirect()->route('department.index')->with('success', 'Department created successfully.');
        }
        return view('errors.404');
    }
    public function department_edit(TenantDepartment $department)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $organisations = Tenant::all();
            $departmentName = Tenant::where('id', $department->tenant_id)->first('name');
            return view('superadmin.departments.edit', compact('department', 'organisations', 'departmentName', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $departmentName = Tenant::where('id', $department->tenant_id)->first('name');
            return view('admin.departments.edit', compact('department', 'departmentName', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function department_update(Request $request, TenantDepartment $department)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department->update($request->all());
            $notification = [
                'message' => 'Department updated successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department->update($request->all());
            $notification = [
                'message' => 'Department updated successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function department_delete(TenantDepartment $department)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $department->delete();
            $notification = [
                'message' => 'Department deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $department->delete();
            $notification = [
                'message' => 'Department deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Receipts */
    public function receipt_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'User') {
            $receipts = Payment::where('customerId', $authUser->id)->orderBy('id', 'desc')->paginate(10);

            return view('user.receipts.index', compact('receipts', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function show_receipt($receipt)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'User') {

            $user = User::with('userDetail')->where('id', $authUser->id)->first();
            $receipt = Payment::with('user')->where('id', $receipt)->first();

            return view('user.receipts.show', compact('receipt', 'user', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    // public function downloadReceipt(Request $request)
    // {
    //     // Create a new FPDI instance
    //     $pdf = new Fpdi();

    //     // Add a page
    //     $pdf->AddPage();

    //     // Set font and font size
    //     $pdf->SetFont('Arial', 'B', 16);

    //     // Add content to the PDF
    //     $pdf->Cell(40, 10, 'Payment Receipt');
    //     $pdf->Ln(); // Line break
    //     $pdf->SetFont('Arial', '', 12);
    //     $pdf->Cell(40, 10, 'Receipt No: RCPT-123456');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Transaction ID: TXN-789012');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Amount: ₦3,000.00');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Paid At: 2025-02-26 10:00 AM');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Email: user@example.com');

    //     // Output the PDF as a download
    //     return Response::make($pdf->Output('S'), 200, [
    //         'Content-Type' => 'application/pdf',
    //         'Content-Disposition' => 'attachment; filename="receipt.pdf"',
    //     ]);
    // }
    /**
     * Show Superadmin-only tenant cleanup form
     */
    public function tenantCleanupForm()
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized: Superadmin only.',
                'alert-type' => 'error'
            ]);
        }

        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', optional($userdetails)->tenant_id)->first();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        // Time-based restriction: allow cleanup only between 00:00 and 04:00 (app timezone)
        $tz = config('app.timezone') ?: 'UTC';
        $now = Carbon::now($tz);
        $allowedWindowStart = $now->copy()->startOfDay();
        $allowedWindowEnd = $allowedWindowStart->copy()->addHours(4);
        $cleanupAllowedNow = $now->gte($allowedWindowStart) && $now->lt($allowedWindowEnd);
        $nextWindowStart = $now->hour >= 4
            ? $now->copy()->addDay()->startOfDay()
            : $now->copy()->startOfDay();
        $nextWindowFormatted = $nextWindowStart->format('M d, Y g:i A') . " ({$tz})";

        return view('superadmin.cleanup.index', compact('authUser', 'userTenant', 'tenants', 'cleanupAllowedNow', 'nextWindowFormatted'));
    }

    /**
     * Execute tenant-specific cleanup in a transaction, removing file_movements,
     * documents, document_recipients and deleting Cloudinary files for documents.
     */
    public function tenantCleanupRun(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized: Superadmin only.',
                'alert-type' => 'error'
            ]);
        }

        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenantId = (int) $request->input('tenant_id');

        // Time-based restriction: allow cleanup only between 00:00 and 04:00 (app timezone)
        $tz = config('app.timezone') ?: 'UTC';
        $now = Carbon::now($tz);
        $allowedWindowStart = $now->copy()->startOfDay();
        $allowedWindowEnd = $allowedWindowStart->copy()->addHours(4);
        $cleanupAllowedNow = $now->gte($allowedWindowStart) && $now->lt($allowedWindowEnd);
        if (!$cleanupAllowedNow) {
            $nextWindowStart = $now->copy()->addDay()->startOfDay();
            // If current time is before today's 4 AM, next window is today midnight
            if ($now->lt($allowedWindowEnd)) {
                $nextWindowStart = $now->copy()->startOfDay();
            }

            return redirect()->back()->with([
                'message' => 'Cleanup is restricted to 12:00 AM–4:00 AM (' . $tz . '). Next window starts at ' . $nextWindowStart->format('M d, Y g:i A') . '.',
                'alert-type' => 'warning',
                'cleanup_stats' => null,
            ]);
        }

        $stats = [
            'file_movements_deleted' => 0,
            'documents_deleted' => 0,
            'document_recipients_deleted' => 0,
            'activities_deleted' => 0,
            'cloudinary_deleted' => 0,
            'cloudinary_failed' => 0,
            // New tables
            'memos_deleted' => 0,
            'memo_movements_deleted' => 0,
            'memo_recipients_deleted' => 0,
            'folders_deleted' => 0,
            'folder_permissions_deleted' => 0,
        ];

        try {
            DB::transaction(function () use ($tenantId, &$stats) {
                // Identify tenant user ids via user_details
                $tenantUserIds = UserDetails::where('tenant_id', $tenantId)->pluck('user_id');

                if ($tenantUserIds->isEmpty()) {
                    return; // Nothing to clean for this tenant
                }

                // Collect documents uploaded by tenant users
                $tenantDocuments = Document::whereIn('uploaded_by', $tenantUserIds)->get(['id', 'file_path']);
                $tenantDocumentIds = $tenantDocuments->pluck('id');

                // Delete FileMovements for tenant users or tenant documents
                FileMovement::whereIn('sender_id', $tenantUserIds)
                    ->orWhereIn('recipient_id', $tenantUserIds)
                    ->orWhereIn('document_id', $tenantDocumentIds)
                    ->chunkById(500, function ($chunk) use (&$stats) {
                        $ids = $chunk->pluck('id');
                        $deleted = FileMovement::whereIn('id', $ids)->delete();
                        $stats['file_movements_deleted'] += $deleted;
                    });

                // Defensive cleanup for any remaining document_recipients involving tenant users
                DocumentRecipient::whereIn('recipient_id', $tenantUserIds)
                    ->orWhereIn('user_id', $tenantUserIds)
                    ->chunkById(500, function ($chunk) use (&$stats) {
                        $ids = $chunk->pluck('id');
                        $deleted = DocumentRecipient::whereIn('id', $ids)->delete();
                        $stats['document_recipients_deleted'] += $deleted;
                    });

                // Delete activities for all users in the selected tenant
                Activity::whereIn('user_id', $tenantUserIds)
                    ->chunkById(500, function ($chunk) use (&$stats) {
                        $ids = $chunk->pluck('id');
                        $deleted = Activity::whereIn('id', $ids)->delete();
                        $stats['activities_deleted'] += $deleted;
                    });

                // Cloudinary deletion intentionally omitted per request. Only DB records are initialized.

                // Delete Documents uploaded by tenant users
                Document::whereIn('id', $tenantDocumentIds)
                    ->chunkById(500, function ($chunk) use (&$stats) {
                        $ids = $chunk->pluck('id');
                        $deleted = Document::whereIn('id', $ids)->delete();
                        $stats['documents_deleted'] += $deleted;
                    });

                // ===== Additional cleanup for memos and folders =====
                // Memos authored by tenant users
                $memoIds = Memo::whereIn('user_id', $tenantUserIds)->pluck('id');

                // Count memo movements and recipients that will be affected
                $memoMovementsQuery = MemoMovement::whereIn('memo_id', $memoIds)
                    ->orWhereIn('sender_id', $tenantUserIds)
                    ->orWhereIn('recipient_id', $tenantUserIds);

                $stats['memo_movements_deleted'] += (clone $memoMovementsQuery)->count();

                // Recipients linked to movements impacted above (cascade will remove on movement delete)
                $stats['memo_recipients_deleted'] += DB::table('memo_recipients')
                    ->whereIn('memo_movement_id', function ($q) use ($memoIds, $tenantUserIds) {
                        $q->select('id')
                          ->from('memo_movements')
                          ->whereIn('memo_id', $memoIds)
                          ->orWhereIn('sender_id', $tenantUserIds)
                          ->orWhereIn('recipient_id', $tenantUserIds);
                    })->count();

                // Delete memo movements (recipients cascade)
                $memoMovementsQuery->chunkById(500, function ($chunk) {
                    MemoMovement::whereIn('id', $chunk->pluck('id'))->delete();
                });

                // Delete memos
                Memo::whereIn('id', $memoIds)->chunkById(500, function ($chunk) use (&$stats) {
                    $ids = $chunk->pluck('id');
                    $deleted = Memo::whereIn('id', $ids)->delete();
                    $stats['memos_deleted'] += $deleted;
                });

                // Folders belonging to tenant (permissions cascade on delete)
                $folderIds = Folder::where('tenant_id', $tenantId)->pluck('id');
                $stats['folders_deleted'] += Folder::where('tenant_id', $tenantId)->count();
                $stats['folder_permissions_deleted'] += DB::table('folder_permissions')
                    ->whereIn('folder_id', $folderIds)->count();

                Folder::whereIn('id', $folderIds)->chunkById(500, function ($chunk) {
                    Folder::whereIn('id', $chunk->pluck('id'))->delete();
                });
            });
        } catch (\Throwable $e) {
            Log::error('Tenant cleanup failed for tenant_id ' . $tenantId . ': ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Cleanup failed: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }

        return redirect()->back()->with([
            'message' => 'Cleanup completed successfully. '
                . 'FileMovements: ' . $stats['file_movements_deleted']
                . ', Documents: ' . $stats['documents_deleted']
                . ', DocumentRecipients: ' . $stats['document_recipients_deleted']
                . ', Activities: ' . $stats['activities_deleted']
                . ', Memos: ' . $stats['memos_deleted']
                . ', MemoMovements: ' . $stats['memo_movements_deleted']
                . ', MemoRecipients: ' . $stats['memo_recipients_deleted']
                . ', Folders: ' . $stats['folders_deleted']
                . ', FolderPermissions: ' . $stats['folder_permissions_deleted'],
            'cleanup_stats' => $stats,
            'alert-type' => 'success'
        ]);
    }

    /**
     * Superadmin-only Tenant Usage Tracking view
     * Provides daily/monthly sent/received counts for tenant users within a date range,
     * user counts, recent transactions, and summary totals.
     */
    public function tenantUsage(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized: Superadmin only.',
                'alert-type' => 'error'
            ]);
        }

        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', optional($userdetails)->tenant_id)->first();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        $request->validate([
            'tenant_id' => 'nullable|exists:tenants,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $selectedTenantId = $request->input('tenant_id');
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : Carbon::now()->endOfDay();

        $dailySeries = [
            'labels' => [],
            'sent' => [],
            'received' => [],
        ];
        $monthlySeries = [
            'labels' => [],
            'sent' => [],
            'received' => [],
        ];
        $userCount = 0;
        $totals = [
            'sent' => 0,
            'received' => 0,
        ];
        $recent = collect();

        if ($selectedTenantId) {
            $tenantUserIds = UserDetails::where('tenant_id', $selectedTenantId)->pluck('user_id');

            $userCount = $tenantUserIds->count();

            // Totals
            $totals['sent'] = DB::table('file_movements')
                ->whereIn('sender_id', $tenantUserIds)
                ->whereBetween('created_at', [$from, $to])
                ->count();
            $totals['received'] = DB::table('file_movements')
                ->whereIn('recipient_id', $tenantUserIds)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            // Daily aggregates
            $dailySent = DB::table('file_movements')
                ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->whereIn('sender_id', $tenantUserIds)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy('day');
            $dailyReceived = DB::table('file_movements')
                ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->whereIn('recipient_id', $tenantUserIds)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy('day');

            // Build continuous daily series from from..to
            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $day = $cursor->toDateString();
                $dailySeries['labels'][] = $day;
                $dailySeries['sent'][] = (int) (optional($dailySent->get($day))->cnt ?? 0);
                $dailySeries['received'][] = (int) (optional($dailyReceived->get($day))->cnt ?? 0);
                $cursor->addDay();
            }

            // Monthly aggregates (YYYY-MM)
            $monthlySent = DB::table('file_movements')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->whereIn('sender_id', $tenantUserIds)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');
            $monthlyReceived = DB::table('file_movements')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->whereIn('recipient_id', $tenantUserIds)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            // Build monthly series across months in range
            $monthCursor = $from->copy()->startOfMonth();
            while ($monthCursor->lte($to)) {
                $monthKey = $monthCursor->format('Y-m');
                $monthlySeries['labels'][] = $monthKey;
                $monthlySeries['sent'][] = (int) (optional($monthlySent->get($monthKey))->cnt ?? 0);
                $monthlySeries['received'][] = (int) (optional($monthlyReceived->get($monthKey))->cnt ?? 0);
                $monthCursor->addMonth();
            }

            // Recent transactions (limit 50)
            $recent = FileMovement::with([
                    'document:id,title',
                    'sender:id,name',
                    'recipient:id,name'
                ])
                ->where(function ($q) use ($tenantUserIds) {
                    $q->whereIn('sender_id', $tenantUserIds)
                      ->orWhereIn('recipient_id', $tenantUserIds);
                })
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        }

        return view('superadmin.usage.index', [
            'authUser' => $authUser,
            'userTenant' => $userTenant,
            'tenants' => $tenants,
            'selectedTenantId' => $selectedTenantId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'userCount' => $userCount,
            'totals' => $totals,
            'dailySeries' => $dailySeries,
            'monthlySeries' => $monthlySeries,
            'recent' => $recent,
        ]);
    }

    /**
     * Export tenant usage report as CSV for a selected tenant and date range.
     * Includes daily sent/received counts to balance detail and size.
     */
    public function tenantUsageExport(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser || $authUser->default_role !== 'superadmin') {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized: Superadmin only.',
                'alert-type' => 'error'
            ]);
        }

        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $tenantId = (int) $validated['tenant_id'];
        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();

        if ($from->gt($to)) {
            return redirect()->back()->with([
                'message' => 'Invalid date range: From must be before To.',
                'alert-type' => 'error'
            ]);
        }

        $tenant = Tenant::find($tenantId);
        $tenantUserIds = UserDetails::where('tenant_id', $tenantId)->pluck('user_id');
        $userCount = $tenantUserIds->count();

        // Aggregate daily counts for sent and received within date range
        $dailySent = DB::table('file_movements')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->whereIn('sender_id', $tenantUserIds)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyReceived = DB::table('file_movements')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->whereIn('recipient_id', $tenantUserIds)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $fileName = sprintf(
            'tenant-usage-%s-%s_to_%s.csv',
            Str::slug($tenant->name ?? 'tenant'),
            $from->toDateString(),
            $to->toDateString()
        );

        // Stream CSV to handle large ranges efficiently
        return Response::streamDownload(function () use ($tenant, $from, $to, $userCount, $dailySent, $dailyReceived) {
            $out = fopen('php://output', 'w');
            // Metadata header rows
            fputcsv($out, ['Tenant', $tenant->name ?? 'Unknown']);
            fputcsv($out, ['From', $from->toDateString()]);
            fputcsv($out, ['To', $to->toDateString()]);
            fputcsv($out, ['Users', $userCount]);
            // Blank line
            fputcsv($out, []);
            // Data header
            fputcsv($out, ['Date', 'Files Sent', 'Files Received']);

            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $day = $cursor->toDateString();
                $sent = (int) (optional($dailySent->get($day))->cnt ?? 0);
                $received = (int) (optional($dailyReceived->get($day))->cnt ?? 0);
                fputcsv($out, [$day, $sent, $received]);
                $cursor->addDay();
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

}
