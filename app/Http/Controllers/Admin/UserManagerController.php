<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagerController extends Controller
{
    /**
     * Server-side DataTables endpoint for Admin Users
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->default_role, ['Admin', 'IT Admin'])) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $tenantId = optional($user->userDetail)->tenant_id;

        $columns = [
            0 => 'id',
            1 => 'user.name',
            2 => 'designation',
            3 => 'tenant_department.name',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = UserDetails::query()
            ->with(['user', 'tenant_department'])
            ->where('tenant_id', $tenantId);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%$searchValue%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('designation', 'like', $like)
                  ->orWhereHas('user', function ($uq) use ($like) {
                      $uq->where('name', 'like', $like);
                  })
                  ->orWhereHas('tenant_department', function ($dq) use ($like) {
                      $dq->where('name', 'like', $like);
                  });
            });
        }

        // Sorting
        if ($orderColumn === 'user.name') {
            $baseQuery->leftJoin('users', 'users.id', '=', 'user_details.user_id')
                      ->orderBy('users.name', $orderDir)
                      ->select('user_details.*');
        } elseif ($orderColumn === 'tenant_department.name') {
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
            $userModel = $ud->user;
            $dept = $ud->tenant_department;

            $action = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . route('user.edit', $ud) . '" class="dropdown-item">Edit</a>';

            if (optional($userModel)->is_active) {
                $action .= '<form method="POST" action="' . route('user.deactivate', optional($userModel)->id) . '" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to deactivate this user? They will not be able to log in until reactivated.\')">'
                        .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="PATCH">'
                        .'<button type="submit" class="dropdown-item text-warning" style="border: none; background: none; text-align: left; width: 100%;">Deactivate</button>'
                        .'</form>';
            } else {
                $action .= '<form method="POST" action="' . route('user.activate', optional($userModel)->id) . '" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to activate this user?\')">'
                        .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="PATCH">'
                        .'<button type="submit" class="dropdown-item text-success" style="border: none; background: none; text-align: left; width: 100%;">Activate</button>'
                        .'</form>';
            }

            $action .= '</div></div>';

            $data[] = [
                'index' => $indexStart + $i,
                'name' => '<a href="' . route('user.view', $ud) . '">' . e(optional($userModel)->name) . '</a>',
                'designation' => e($ud->designation),
                'department' => e(optional($dept)->name ?? ''),
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
}