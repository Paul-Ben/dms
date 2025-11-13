@extends('dashboards.index')

@section('content')
    <div class="container-fluid pt-4 px-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="bg-light rounded p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h6 class="mb-4">Tenant Analytics</h6>
                        <div>
                            <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                        </div>
                    </div>

                    <form action="{{ route('admin.analytics.index') }}" method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-sm-12 col-xl-3 mb-3">
                                <label class="form-label" for="from">From</label>
                                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control" />
                            </div>
                            <div class="col-sm-12 col-xl-3 mb-3">
                                <label class="form-label" for="to">To</label>
                                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control" />
                            </div>
                            <div class="col-sm-12 col-xl-3 mb-3">
                                <label class="form-label" for="role">Role</label>
                                <select id="role" name="role" class="form-select">
                                    <option value="">All roles</option>
                                    @foreach($availableRoles as $role)
                                        <option value="{{ $role }}" {{ $roleFilter === $role ? 'selected' : '' }}>{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-xl-3 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">Apply</button>
                            </div>
                        </div>
                    </form>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="bg-white rounded p-3 border">
                                <div class="text-muted small">Tenant</div>
                                <div class="h6 mb-0">{{ optional($tenant)->code ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-white rounded p-3 border">
                                <div class="text-muted small">Users</div>
                                <div class="h5 mb-0">{{ number_format($userCount) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-white rounded p-3 border">
                                <div class="text-muted small">Logins</div>
                                <div class="h5 mb-0">{{ number_format($totalLogins) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-white rounded p-3 border">
                                <div class="text-muted small">Actions</div>
                                <div class="h5 mb-0">{{ number_format($totalActions) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- <div class="row mb-4">
                        <div class="col-12">
                            <div class="bg-white rounded p-3 border">
                                <h6 class="mb-3">Daily Login Frequency</h6>
                                <div class="chart-wrapper" style="max-height: 320px; overflow-x: auto; overflow-y: auto;">
                                    <canvas id="loginChart" height="280" class="chart-canvas" style="min-width: 900px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="bg-white rounded p-3 border">
                                <h6 class="mb-3">Active Minutes per Day (approx)</h6>
                                <div class="chart-wrapper" style="max-height: 320px; overflow-x: auto; overflow-y: auto;">
                                    <canvas id="activeMinutesChart" height="280" class="chart-canvas" style="min-width: 900px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="bg-white rounded p-3 border">
                                <h6 class="mb-3">Feature Usage</h6>
                                <div style="max-height: 300px;">
                                    <canvas id="featureUsageChart" height="260"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-white rounded p-3 border">
                                <h6 class="mb-3">Feature Usage (Table)</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th class="text-end">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($featureUsageLabels as $i => $label)
                                                <tr>
                                                    <td>{{ $label }}</td>
                                                    <td class="text-end">{{ number_format($featureUsageCounts[$i] ?? 0) }}</td>
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
                                <h6 class="mb-3">Actions Per User</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($actionsPerUser as $row)
                                                @php $u = $usersById[$row->user_id] ?? null; @endphp
                                                <tr>
                                                    <td>{{ optional($u)->name ?? 'Unknown' }}</td>
                                                    <td>{{ optional($u)->default_role ?? '—' }}</td>
                                                    <td class="text-end">{{ number_format($row->count) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No actions found for the selected filters.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const loginLabels = @json($dailyLabels);
            const loginSeries = @json($loginSeries);
            const activeMinutesSeries = @json($activeMinutesSeries);
            const featureLabels = @json($featureUsageLabels);
            const featureCounts = @json($featureUsageCounts);

            // Login Chart
            const loginCtx = document.getElementById('loginChart');
            if (loginCtx) {
                try { loginCtx.style.minWidth = Math.max(900, (loginLabels?.length || 0) * 30) + 'px'; } catch(e){}
                new Chart(loginCtx, {
                    type: 'line',
                    data: {
                        labels: loginLabels,
                        datasets: [{
                            label: 'Logins',
                            data: loginSeries,
                            borderColor: 'rgba(13, 110, 253, 1)',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            x: { title: { display: true, text: 'Date' } },
                            y: { title: { display: true, text: 'Count' }, beginAtZero: true }
                        }
                    }
                });
            }

            // Active Minutes Chart
            const activeCtx = document.getElementById('activeMinutesChart');
            if (activeCtx) {
                try { activeCtx.style.minWidth = Math.max(900, (loginLabels?.length || 0) * 30) + 'px'; } catch(e){}
                new Chart(activeCtx, {
                    type: 'line',
                    data: {
                        labels: loginLabels,
                        datasets: [{
                            label: 'Active minutes',
                            data: activeMinutesSeries,
                            borderColor: 'rgba(25, 135, 84, 1)',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            x: { title: { display: true, text: 'Date' } },
                            y: { title: { display: true, text: 'Minutes' }, beginAtZero: true }
                        }
                    }
                });
            }

            // Feature Usage Chart
            const featureCtx = document.getElementById('featureUsageChart');
            if (featureCtx) {
                new Chart(featureCtx, {
                    type: 'doughnut',
                    data: {
                        labels: featureLabels,
                        datasets: [{
                            label: 'Count',
                            data: featureCounts,
                            backgroundColor: [
                                'rgba(13, 110, 253, 0.6)',
                                'rgba(220, 53, 69, 0.6)',
                                'rgba(25, 135, 84, 0.6)',
                                'rgba(255, 193, 7, 0.6)',
                                'rgba(32, 201, 151, 0.6)',
                                'rgba(111, 66, 193, 0.6)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } }
                    }
                });
            }
        })();
    </script>
@endsection