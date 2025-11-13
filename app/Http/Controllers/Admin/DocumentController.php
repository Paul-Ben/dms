<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\UserDetails;
use App\Models\FileMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    /**
     * DataTables server-side endpoint for admin documents.
     * Returns JSON with draw, recordsTotal, recordsFiltered, data.
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->default_role !== 'Admin') {
            abort(403, 'Unauthorized');
        }

        $tenantId = optional($user->userDetail)->tenant_id;

        $draw = (int) ($request->input('draw', 0));
        $start = (int) ($request->input('start', 0));
        $length = (int) ($request->input('length', 10));
        $searchValue = $request->input('search.value');

        $orderColumnIndex = (int) ($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = [
            'documents.id',
            'documents.docuent_number',
            'documents.title',
            'documents.status',
        ];
        $orderColumn = $columns[$orderColumnIndex] ?? 'documents.id';
        $orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? $orderDir : 'asc';

        $baseQuery = DB::table('documents')
            ->select(['documents.id', 'documents.docuent_number', 'documents.title', 'documents.status'])
            ->leftJoin('user_details', 'user_details.user_id', '=', 'documents.uploaded_by');
        if ($tenantId) {
            $baseQuery->where('user_details.tenant_id', '=', $tenantId);
        }

        // Total before filtering
        $recordsTotal = (clone $baseQuery)->count('documents.id');

        // Apply search filter
        if ($searchValue) {
            $like = '%' . $searchValue . '%';
            $baseQuery->where(function ($q) use ($like) {
                $q->where('documents.docuent_number', 'like', $like)
                  ->orWhere('documents.title', 'like', $like)
                  ->orWhere('documents.status', 'like', $like);
            });
        }

        $recordsFiltered = (clone $baseQuery)->count('documents.id');

        $rows = $baseQuery
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $data = [];
        foreach ($rows as $i => $row) {
            $data[] = [
                'index' => $indexStart + $i,
                'docuent_number' => '<a href="' . route('document.myview', $row->id) . '">' . e($row->docuent_number) . '</a>',
                'title' => $row->title,
                'status' => $row->status,
                'action' => '<div class="nav-item dropdown">'
                    .'<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>'
                    .'<div class="dropdown-menu">'
                    .'<a href="' . route('document.myview', $row->id) . '" class="dropdown-item">Open</a>'
                    .'<a href="' . route('document.send', $row->id) . '" class="dropdown-item">Minute the Mail</a>'
                    .'<a href="' . route('document.sendout', $row->id) . '" class="dropdown-item">Send Externally</a>'
                    .'</div></div>',
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Server-side DataTables for Admin Received Documents
     */
    public function receivedData(Request $request)
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
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'file_movements.updated_at';

        $baseQuery = FileMovement::query()
            ->with(['document', 'sender'])
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
                ->orWhereHas('document', function ($dq) use ($searchValue) {
                    $dq->where('status', 'like', "%$searchValue%");
                });
            });
        }

        // Apply ordering
        if ($orderColumn === 'sender.name') {
            $baseQuery->leftJoin('users as sender', 'sender.id', '=', 'file_movements.sender_id')
                      ->orderBy('sender.name', $orderDir)
                      ->select('file_movements.*');
        } elseif (str_starts_with($orderColumn, 'documents.')) {
            $baseQuery->leftJoin('documents', 'documents.id', '=', 'file_movements.document_id')
                      ->orderBy(str_replace('documents.', 'documents.', $orderColumn), $orderDir)
                      ->select('file_movements.*');
        } else {
            $baseQuery->orderBy(str_replace('file_movements.', '', $orderColumn), $orderDir);
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $data = [];
        foreach ($rows as $i => $fm) {
            $doc = $fm->document;
            $sender = $fm->sender;
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . route('document.view', $fm->id) . '">' . e($doc->docuent_number) . '</a>',
                'subject' => e($doc->title),
                'submitted_by' => e(optional($sender)->name ?? 'NA'),
                'status' => e($doc->status),
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
     * Server-side DataTables for Admin Sent Documents
     */
    public function sentData(Request $request)
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
        $orderDir = $request->input('order.0.dir', 'asc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'file_movements.updated_at';

        $baseQuery = FileMovement::query()
            ->with(['document', 'recipient.userDetail.tenant'])
            ->where('sender_id', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->whereHas('document', function ($dq) use ($searchValue) {
                    $dq->where('docuent_number', 'like', "%$searchValue%")
                       ->orWhere('title', 'like', "%$searchValue%");
                })
                ->orWhereHas('recipient', function ($rq) use ($searchValue) {
                    $rq->whereHas('userDetail', function ($ud) use ($searchValue) {
                        $ud->where('designation', 'like', "%$searchValue%")
                           ->orWhereHas('tenant', function ($tq) use ($searchValue) {
                               $tq->where('name', 'like', "%$searchValue%");
                           });
                    });
                });
            });
        }

        if (str_starts_with($orderColumn, 'documents.')) {
            $baseQuery->leftJoin('documents', 'documents.id', '=', 'file_movements.document_id')
                      ->orderBy(str_replace('documents.', 'documents.', $orderColumn), $orderDir)
                      ->select('file_movements.*');
        } else {
            $baseQuery->orderBy(str_replace('file_movements.', '', $orderColumn), $orderDir);
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $data = [];
        foreach ($rows as $i => $fm) {
            $doc = $fm->document;
            $recipient = $fm->recipient; // User
            $designation = optional(optional($recipient)->userDetail)->designation;
            $tenantName = optional(optional(optional($recipient)->userDetail)->tenant)->name;
            $sentTo = trim(($designation ? $designation : '') . ($tenantName ? ', ' . $tenantName : ''));
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => '<a href="' . route('document.view', $fm->id) . '">' . e($doc->docuent_number) . '</a>',
                'subject' => e($doc->title),
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