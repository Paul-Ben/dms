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
                    @role('IT Admin')
                    <a class="btn btn-sm btn-primary" href="{{ route('user.create') }}">Add User</a>
                    @endrole
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="adminUsersTable" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Designation</th>
                            <th scope="col">Department</th>
                            @role('IT Admin')
                            <th scope="col">Action</th>
                            @endrole
                        </tr>
                    </thead>
                    <tbody>
                        

                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <!-- Table End -->
    <script>
        $(document).ready(function() {
            var columns = [
                { data: 'index', name: 'index', orderable: false, searchable: false },
                { data: 'name', name: 'users.name' },
                { data: 'designation', name: 'designation' },
                { data: 'department', name: 'tenant_departments.name' }
            ];
            @role('IT Admin')
            columns.push({ data: 'action', name: 'action', orderable: false, searchable: false });
            @endrole

            $('#adminUsersTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.users.data') }}',
                    type: 'GET'
                },
                columns: columns,
                order: [[1, 'asc']],
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
