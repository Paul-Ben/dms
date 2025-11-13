<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Memo;
use App\Models\MemoMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemoController extends Controller
{
    /**
     * Server-side DataTables for Staff Memo Index (My Memos)
     */
    public function indexData(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->default_role, ['Staff', 'IT Admin'])) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'memos.id',
            1 => 'memos.docuent_number',
            2 => 'memos.title',
            3 => 'memos.status',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'memos.id';
        $orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? $orderDir : 'desc';

        $baseQuery = DB::table('memos')
            ->select(['memos.id', 'memos.docuent_number', 'memos.title', 'memos.status'])
            ->where('user_id', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%" . $searchValue . "%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('memos.docuent_number', 'like', $like)
                  ->orWhere('memos.title', 'like', $like);
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        $indexStart = $start + 1;
        foreach ($rows as $i => $row) {
            $viewUrl = route('memo.view', $row->id);
            $sendInternalUrl = route('memo.send', $row->id);
            $sendExternalUrl = route('memo.sendout', $row->id);
            $actionHtml = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Action</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . e($viewUrl) . '" class="dropdown-item">Open</a>'
                .'<a href="' . e($sendInternalUrl) . '" class="dropdown-item">Send Internally</a>'
                .'<a href="' . e($sendExternalUrl) . '" class="dropdown-item">Send Externally</a>'
                .'</div></div>';

            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . e($viewUrl) . '">' . e($row->docuent_number) . '</a>',
                'title' => e($row->title),
                'status' => e($row->status ?? 'Processing'),
                'action' => $actionHtml,
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
     * Server-side DataTables for Staff Received Memos
     */
    public function receivedData(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->default_role, ['Staff', 'IT Admin'])) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'memo_movements.id',
            1 => 'memos.docuent_number',
            2 => 'memos.title',
            3 => 'memos.status',
            4 => 'memo_movements.updated_at',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'memo_movements.updated_at';

        $baseQuery = MemoMovement::query()
            ->with(['memo', 'sender'])
            ->where('recipient_id', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->whereHas('memo', function ($mq) use ($searchValue) {
                    $mq->where('docuent_number', 'like', "%$searchValue%")
                       ->orWhere('title', 'like', "%$searchValue%")
                       ->orWhere('status', 'like', "%$searchValue%");
                })
                ->orWhere('id', 'like', "%$searchValue%");
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderBy(DB::raw($orderColumn), $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        $indexStart = $start + 1;
        foreach ($rows as $i => $mm) {
            $memo = $mm->memo;
            $senderName = optional($mm->sender)->name;
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . route('memo.view', optional($memo)->id) . '">' . e(optional($memo)->docuent_number) . '</a>',
                'subject' => e(optional($memo)->title),
                'sent_by' => e($senderName ?? ''),
                'status' => e(optional($memo)->status),
                'date' => e(optional($mm->updated_at)->format('M j, Y g:i A')),
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
     * Server-side DataTables for Staff Sent Memos
     */
    public function sentData(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->default_role, ['Staff', 'IT Admin'])) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'memo_movements.id',
            1 => 'memos.docuent_number',
            2 => 'memos.title',
            3 => 'recipient_details',
            4 => 'memo_movements.updated_at',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'memo_movements.updated_at';

        $baseQuery = MemoMovement::query()
            ->with(['memo', 'recipient.userDetail.tenant', 'recipient.userDetail.tenant_department'])
            ->where('sender_id', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->whereHas('memo', function ($mq) use ($searchValue) {
                    $mq->where('docuent_number', 'like', "%$searchValue%")
                       ->orWhere('title', 'like', "%$searchValue%");
                })
                ->orWhereHas('recipient', function ($rq) use ($searchValue) {
                    $rq->where('name', 'like', "%$searchValue%");
                })
                ->orWhere('id', 'like', "%$searchValue%");
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderBy(DB::raw($orderColumn), $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        $indexStart = $start + 1;
        foreach ($rows as $i => $mm) {
            $memo = $mm->memo;
            $recipient = $mm->recipient;
            $designation = optional(optional($recipient)->userDetail)->designation;
            $deptName = optional(optional(optional($recipient)->userDetail)->tenant_department)->name;
            $tenantCode = optional(optional(optional($recipient)->userDetail)->tenant)->code ?? optional(optional(optional($recipient)->userDetail)->tenant)->name;
            $sentTo = trim(($designation ? $designation : '') . ($deptName ? ', ' . $deptName : '') . ($tenantCode ? ' ' . $tenantCode : ''));
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . route('memo.view', optional($memo)->id) . '">' . e(optional($memo)->docuent_number) . '</a>',
                'subject' => e(optional($memo)->title),
                'sent_to' => e($sentTo),
                'date' => e(optional($mm->updated_at)->format('M j, Y g:i A')),
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