@extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa fa-exclamation-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">User Management</h6>
                {{-- <div>
                    <form method="GET" action="{{route('search.user')}}">
                        <div class="input-group mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Search items..."
                                value="">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                </div> --}}
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('user.create') }}">Add User</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('userUpload.form') }}">Upload User</a>
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous()}}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="superUserMan" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Designation</th>
                            <th scope="col">Department</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="mt-4">
                    {{-- @if ($users->count() > 0)
                        {{ $users->links('pagination::bootstrap-5') }}
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(function() {
            $('#superUserMan').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('superadmin.usermanager.data') }}',
                    type: 'GET'
                },
                columns: [
                    { data: 'index', name: 'index', orderable: false, searchable: false },
                    { data: 'name', name: 'users.name' },
                    { data: 'designation', name: 'tenants.name' },
                    { data: 'department', name: 'tenant_departments.name' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
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
