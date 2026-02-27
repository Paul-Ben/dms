@extends('dashboards.index')

@section('content')
<div class="container py-4">
    @if(session('message'))
        <div class="alert alert-{{ session('alert-type') }}">
            {{ session('message') }}
        </div>
    @endif

    <h2 class="mb-3">My Uploaded Documents</h2>

    <div class="card mb-4">
        <div class="card-header">Uploads Pending Payment</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Doc ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holds as $hold)
                            <tr>
                                <td>{{ $hold->title }}</td>
                                <td>{{ $hold->docuent_number }}</td>
                                <td>₦{{ number_format($hold->amount, 2) }}</td>
                                <td>{{ $hold->payment_status }}</td>
                                <td>
                                    @if(strtolower($hold->payment_status ?? '') !== 'paid')
                                        <a href="{{ route('document.uploads.pay', $hold) }}" class="btn btn-primary btn-sm">Pay</a>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>Paid</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No uploads yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- <div class="card">
        <div class="card-header">Paid & Sent Documents</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Doc ID</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td>{{ $doc->title }}</td>
                                <td>{{ $doc->docuent_number }}</td>
                                <td>{{ $doc->status }}</td>
                                <td>
                                    @if($doc->status === 'processing')
                                        <button class="btn btn-secondary btn-sm" disabled>Sent</button>
                                    @elseif(strtolower($doc->payment_status ?? '') === 'paid')
                                        <button class="btn btn-secondary btn-sm" disabled>Paid</button>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>Unpaid</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No paid documents yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div> --}}
</div>
@endsection
