@extends('dashboards.index')
@section('content')
    <!-- Actions Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0">Database Backups (Spatie)</h6>
                    <div class="d-flex gap-2">
                        <form action="{{ route('superadmin.backups.run') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fa fa-database me-2"></i>Run Full Backup
                            </button>
                        </form>
                        <form action="{{ route('superadmin.backups.run') }}" method="POST">
                            @csrf
                            <input type="hidden" name="only_db" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="fa fa-table me-2"></i>Run Database-only
                            </button>
                        </form>
                        <form action="{{ route('superadmin.backups.clean') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="fa fa-broom me-2"></i>Clean Old Backups
                            </button>
                        </form>
                        <form action="{{ route('superadmin.backups.monitor') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info">
                                <i class="fa fa-heartbeat me-2"></i>Monitor Health
                            </button>
                        </form>
                        <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                                class="fa fa-arrow-left me-2"></i>Back</a>
                    </div>
                </div>
                @if(session('backup_output'))
                    <pre class="text-start small bg-white p-2 border">{{ session('backup_output') }}</pre>
                @endif
                <div class="table-responsive">
                    <table id="SpatieBackupsTable" class="table text-start align-middle table-bordered table-hover mb-0">
                        <thead>
                            <tr class="text-dark">
                                <th scope="col">#</th>
                                <th scope="col">Filename</th>
                                <th scope="col">Disk</th>
                                <th scope="col">Size</th>
                                <th scope="col">Date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($spatieBackups ?? []) as $index => $b)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $b['filename'] }}</td>
                                    <td>{{ $b['disk'] }}</td>
                                    <td>{{ number_format(($b['size'] ?? 0) / 1024 / 1024, 2) }} MB</td>
                                    <td>
                                        @if(!empty($b['last_modified']))
                                            {{ \Carbon\Carbon::createFromTimestamp($b['last_modified'])->toDateTimeString() }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('superadmin.backups.download', ['disk' => $b['disk'], 'path' => urlencode($b['path'])]) }}" class="btn btn-sm btn-primary">Download</a>
                                            <form action="{{ route('superadmin.backups.delete', ['disk' => $b['disk'], 'path' => urlencode($b['path'])]) }}" method="POST" onsubmit="return confirm('Delete this backup file?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="text-center">
                                    <td>0</td>
                                    <td>No backups found</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Actions End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Visitor Activity CSV Backups</h6>
                <div>
                    {{-- <a class="btn btn-sm btn-primary" href="{{ route('visitor.activity.create') }}"><i class="fa fa-plus me-2"></i>Add Visitor Activity</a> --}}
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="fa fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="BackupsTable" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Backup Name</th>
                            <th scope="col">Date</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $key => $backup)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>Backup {{ $backup->created_at }}</td>
                                <td>{{ $backup->created_at }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('backup.download', $backup) }}"
                                        class="btn btn-sm btn-primary">
                                        Download
                                    </a>
                                    <form action="{{ route('backup.delete', $backup )}}" method="POST">
                                        @csrf
                                        @method('Delete')
                                    <button class="btn btn-sm btn-danger" type="submit">
                                        Delete
                                    </button>
                                    </form>
                                    </div>
                                    
                                    
                                </td>
                            </tr>
                        @empty
                            <tr class="text-center">
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{-- {{ $activities->links() }} --}}
            </div>
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(document).ready(function() {
            $('#BackupsTable').DataTable({
                "order": [
                    [0, "desc"]
                ], // Optional: order by latest
                "pageLength": 10
            });
            $('#SpatieBackupsTable').DataTable({
                "order": [
                    [0, "desc"]
                ],
                "pageLength": 10
            });
        });
    </script>
@endsection
