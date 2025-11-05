@extends('dashboards.index')

@section('content')
    <div class="container-fluid pt-4 px-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="bg-light rounded p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h6 class="mb-4">Tenant Usage Tracking</h6>
                        <div>
                            <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                        </div>
                    </div>

                    <form action="{{ route('superadmin.tenant.usage') }}" method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-sm-12 col-xl-4 mb-3">
                                <label class="form-label" for="tenant_id">Select Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" required>
                                    <option value="">Select tenant</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ (string)$selectedTenantId === (string)$tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-xl-3 mb-3">
                                <label class="form-label" for="from">From</label>
                                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control" />
                            </div>
                            <div class="col-sm-12 col-xl-3 mb-3">
                                <label class="form-label" for="to">To</label>
                                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control" />
                            </div>
                            <div class="col-sm-12 col-xl-2 d-flex align-items-end mb-3">
                                <div class="d-grid gap-2 w-100">
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                    @if($selectedTenantId)
                                        <a href="{{ route('superadmin.tenant.usage.export', ['tenant_id' => $selectedTenantId, 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary" id="exportBtn">
                                            <i class="fa fa-download me-2"></i>Export CSV
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary" disabled title="Select tenant and date range to export">Export CSV</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>

                    @if($selectedTenantId)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="bg-white rounded p-3 border">
                                    <div class="text-muted small">Users</div>
                                    <div class="h5 mb-0">{{ number_format($userCount) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-white rounded p-3 border">
                                    <div class="text-muted small">Files Sent</div>
                                    <div class="h5 mb-0">{{ number_format($totals['sent']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-white rounded p-3 border">
                                    <div class="text-muted small">Files Received</div>
                                    <div class="h5 mb-0">{{ number_format($totals['received']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-white rounded p-3 border">
                                    <div class="text-muted small">Period</div>
                                    <div class="h6 mb-0">{{ $from }} – {{ $to }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="bg-white rounded p-3 border">
                                    @php
                                        $fromDate = \Carbon\Carbon::parse($from);
                                        $toDate = \Carbon\Carbon::parse($to);
                                        $rangeDays = $fromDate->diffInDays($toDate) + 1;
                                        $isMonthlyView = $rangeDays > 31;
                                    @endphp
                                    <h6 class="mb-3">{{ $isMonthlyView ? 'Monthly' : 'Daily' }} File Activity</h6>
                                    <div class="chart-wrapper" style="max-height: 320px; overflow-x: auto; overflow-y: auto;">
                                        <canvas id="usageChart" height="280" class="chart-canvas" style="min-width: 900px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="bg-white rounded p-3 border">
                                    <h6 class="mb-3">Monthly File Activity</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th class="text-end">Sent</th>
                                                    <th class="text-end">Received</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($monthlySeries['labels'] as $i => $month)
                                                    <tr>
                                                        <td>{{ $month }}</td>
                                                        <td class="text-end">{{ number_format($monthlySeries['sent'][$i] ?? 0) }}</td>
                                                        <td class="text-end">{{ number_format($monthlySeries['received'][$i] ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="bg-white rounded p-3 border">
                                    <h6 class="mb-3">Recent File Transactions</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Timestamp</th>
                                                    <th>Document</th>
                                                    <th>Sender</th>
                                                    <th>Recipient</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recent as $fm)
                                                    <tr>
                                                        <td>{{ \Carbon\Carbon::parse($fm->created_at)->format('Y-m-d H:i') }}</td>
                                                        <td>{{ optional($fm->document)->title }}</td>
                                                        <td>{{ optional($fm->sender)->name }}</td>
                                                        <td>{{ optional($fm->recipient)->name }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No transactions found for the selected period.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">Select a tenant and date range to view usage.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const isMonthlyView = (function() {
                try {
                    const fromStr = @json($from);
                    const toStr = @json($to);
                    const from = new Date(fromStr + 'T00:00:00');
                    const to = new Date(toStr + 'T00:00:00');
                    const diffMs = Math.abs(to - from);
                    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
                    return diffDays > 31;
                } catch (e) {
                    return false;
                }
            })();

            const labels = isMonthlyView ? @json($monthlySeries['labels']) : @json($dailySeries['labels']);
            const sent = isMonthlyView ? @json($monthlySeries['sent']) : @json($dailySeries['sent']);
            const received = isMonthlyView ? @json($monthlySeries['received']) : @json($dailySeries['received']);

            const axisTitle = isMonthlyView ? 'Month' : 'Date';

            const ctx = document.getElementById('usageChart');
            if (ctx) {
                // Dynamically widen the canvas to enable horizontal scrolling for many labels
                try {
                    const minWidth = Math.max(900, (labels?.length || 0) * (isMonthlyView ? 60 : 30));
                    ctx.style.minWidth = minWidth + 'px';
                } catch (e) {}
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Sent',
                                data: sent,
                                borderColor: 'rgba(220, 53, 69, 1)',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                tension: 0.3
                            },
                            {
                                label: 'Received',
                                data: received,
                                borderColor: 'rgba(25, 135, 84, 1)',
                                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                tension: 0.3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' }
                        },
                        scales: {
                            x: { title: { display: true, text: axisTitle } },
                            y: { title: { display: true, text: 'Count' }, beginAtZero: true }
                        }
                    }
                });
            }
        })();
    </script>
@endsection