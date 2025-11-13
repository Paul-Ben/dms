<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    /**
     * Server-side DataTables for User Receipts Index
     */
    public function indexData(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->default_role !== 'User') {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized',
            ], 200);
        }

        $columns = [
            0 => 'payments.id',
            1 => 'payments.document_no',
            2 => 'payments.reference',
            3 => 'payments.transAmount',
            4 => 'payments.transDate',
        ];

        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = $request->input('search.value');
        $orderColumnIndex = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'payments.id';
        $orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? $orderDir : 'desc';

        $baseQuery = Payment::query()
            ->where('customerId', $user->id);

        $recordsTotal = (clone $baseQuery)->count();

        if ($searchValue) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('document_no', 'like', "%$searchValue%")
                  ->orWhere('reference', 'like', "%$searchValue%")
                  ->orWhere('transAmount', 'like', "%$searchValue%")
                  ->orWhere('transDate', 'like', "%$searchValue%");
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderBy(DB::raw($orderColumn), $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $indexStart = $start + 1;
        $data = [];
        foreach ($rows as $i => $receipt) {
            $viewUrl = route('receipt.show', $receipt->id);
            $data[] = [
                'index' => $indexStart + $i,
                'doc_no' => e($receipt->document_no),
                'reference' => e($receipt->reference),
                'amount' => e($receipt->transAmount),
                'date' => e($receipt->transDate),
                'action' => '<a href="' . e($viewUrl) . '" target="__blank">View</a>',
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