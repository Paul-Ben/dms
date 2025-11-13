<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\FileMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Server-side DataTables for Staff Documents Index ("My Documents")
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
            0 => 'documents.id',
            1 => 'documents.docuent_number',
            2 => 'documents.title',
            3 => 'documents.status',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'documents.id';
        $orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? $orderDir : 'desc';

        $baseQuery = DB::table('documents')
            ->select(['documents.id', 'documents.docuent_number', 'documents.title', 'documents.status'])
            ->where('uploaded_by', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $like = "%" . $searchValue . "%";
            $baseQuery->where(function ($q) use ($like) {
                $q->where('documents.docuent_number', 'like', $like)
                  ->orWhere('documents.title', 'like', $like)
                  ->orWhere('documents.status', 'like', $like);
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
            $viewUrl = route('document.myview', $row->id);
            $sendInternalUrl = route('document.send', $row->id);
            $actionHtml = '<div class="nav-item dropdown">'
                .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Action</a>'
                .'<div class="dropdown-menu">'
                .'<a href="' . e($viewUrl) . '" class="dropdown-item">Open</a>'
                .'<a href="' . e($sendInternalUrl) . '" class="dropdown-item">Send Internally</a>'
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
     * Server-side DataTables for Staff Received Documents
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
            0 => 'file_movements.id',
            1 => 'documents.docuent_number',
            2 => 'documents.title',
            3 => 'sender.name',
            4 => 'documents.status',
            5 => 'file_movements.updated_at',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'file_movements.updated_at';

        $baseQuery = FileMovement::query()
            ->with(['document', 'sender.userDetail.tenant_department'])
            ->where('recipient_id', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->whereHas('document', function ($dq) use ($searchValue) {
                    $dq->where('docuent_number', 'like', "%$searchValue%")
                       ->orWhere('title', 'like', "%$searchValue%");
                })
                ->orWhereHas('sender', function ($sq) use ($searchValue) {
                    $sq->where('name', 'like', "%$searchValue%");
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
        foreach ($rows as $i => $fm) {
            $doc = $fm->document;
            $sender = $fm->sender;
            $designation = optional(optional($sender)->userDetail)->designation;
            $deptName = optional(optional(optional($sender)->userDetail)->tenant_department)->name;
            $submittedBy = trim(($designation ? $designation : '') . ($deptName ? ' ' . $deptName : ''));
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . route('document.view', $fm->id) . '">' . e(optional($doc)->docuent_number) . '</a>',
                'subject' => e(optional($doc)->title),
                'submitted_by' => e($submittedBy),
                'status' => e(optional($doc)->status),
                'date' => e(optional($fm->updated_at)->format('M j, Y g:i A')),
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
     * Server-side DataTables for Staff Sent Documents
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
            0 => 'file_movements.id',
            1 => 'documents.docuent_number',
            2 => 'documents.title',
            3 => 'recipient_details',
            4 => 'file_movements.updated_at',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'file_movements.updated_at';

        $baseQuery = FileMovement::query()
            ->with(['document', 'recipient.userDetail.tenant', 'recipient.userDetail.tenant_department'])
            ->where('sender_id', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->whereHas('document', function ($dq) use ($searchValue) {
                    $dq->where('docuent_number', 'like', "%$searchValue%")
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
        foreach ($rows as $i => $fm) {
            $doc = $fm->document;
            $recipient = $fm->recipient;
            $designation = optional(optional($recipient)->userDetail)->designation;
            $deptName = optional(optional(optional($recipient)->userDetail)->tenant_department)->name;
            $tenantName = optional(optional(optional($recipient)->userDetail)->tenant)->code ?? optional(optional(optional($recipient)->userDetail)->tenant)->name;
            $sentTo = trim(($designation ? $designation : '') . ($deptName ? ', ' . $deptName : '') . ($tenantName ? ' ' . $tenantName : ''));
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . route('document.view', $fm->id) . '">' . e(optional($doc)->docuent_number) . '</a>',
                'subject' => e(optional($doc)->title),
                'sent_to' => e($sentTo),
                'date' => e(optional($fm->updated_at)->format('M j, Y g:i A')),
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