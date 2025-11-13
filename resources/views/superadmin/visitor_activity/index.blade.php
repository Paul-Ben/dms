@extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">

            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Visitor Activity</h6>
                <div>
                    {{-- <a class="btn btn-sm btn-primary" href="{{ route('visitor.activity.create') }}"><i class="fa fa-plus me-2"></i>Add Visitor Activity</a> --}}
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="fa fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="visitorActivityTable" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Visitor Name</th>
                            <th scope="col">Ip Address</th>
                            <th scope="col">URL</th>
                            <th scope="col">Browser</th>
                            <th scope="col">Device</th>
                            {{-- <th scope="col">Country</th> --}}
                            {{-- <th scope="col">Region</th>
                            <th scope="col">City</th>
                            <th scope="col">Method</th> --}}
                            <th scope="col">Date</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                {{-- {{ $activities->links() }} --}}
            </div>
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(function() {
            $('#visitorActivityTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('superadmin.visitor.activity.data') }}',
                    type: 'GET'
                },
                columns: [
                    { data: 'index', name: 'index', orderable: false, searchable: false },
                    { data: 'visitor_name', name: 'users.name' },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'url', name: 'url', orderable: false },
                    { data: 'browser', name: 'browser' },
                    { data: 'device', name: 'device' },
                    { data: 'date', name: 'created_at' }
                ],
                order: [[6, 'desc']],
                lengthMenu: [10, 25, 50, 100],
                language: {
                    searchPlaceholder: 'Search here...',
                    zeroRecords: 'No matching records found',
                    lengthMenu: 'Show entries'
                }
            });
        });
    </script>
@endsection
