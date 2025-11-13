<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TenantDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    /**
     * Server-side DataTables endpoint for Admin Departments
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

        $baseQuery = TenantDepartment::query()
            ->where('tenant_id', $tenantId);

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

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $csrf = csrf_token();
        $data = [];
        foreach ($rows as $i => $dept) {
            $action = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . route('department.edit', $dept) . '" class="dropdown-item">Edit</a>'
                .'<form action="' . route('department.delete', $dept) . '" method="POST" onsubmit="return confirm(\'Are you sure?\');">'
                .'<input type="hidden" name="_token" value="' . $csrf . '">' . '<input type="hidden" name="_method" value="DELETE">'
                .'<button class="dropdown-item" style="background-color: rgb(235, 78, 78)" type="submit">Delete</button>'
                .'</form>'
                .'</div></div>';

            $data[] = [
                'index' => $indexStart + $i,
                'name' => e($dept->name),
                'email' => e($dept->email),
                'phone' => e($dept->phone),
                'status' => e($dept->status),
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