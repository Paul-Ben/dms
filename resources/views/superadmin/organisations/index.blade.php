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
        <!-- Button End -->

        <!-- Table Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light text-center rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Organisation Management</h6>
                    {{-- <div>
                        <form method="GET" action="{{route('search.org')}}">
                            <div class="input-group mb-3">
                                <input type="text" name="search" class="form-control" placeholder="Search items..."
                                    value="">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                    </div> --}}
                    <div>
                        <a class="btn btn-sm btn-primary" href="{{ route('organisation.create') }}">Add Tenant</a>
                        <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                                class="fa fa-arrow-left me-2"></i>Back</a>
                    </div>

                </div>
                <div class="table-responsive">
                    <table id="superOrganisation" class="table text-start align-middle table-bordered table-hover mb-0">
                        <thead>
                            <tr class="text-dark">
                                <th scope="col">#</th>
                                {{-- <th scope="col">Logo</th> --}}
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                {{-- <div class="mt-4">{{$organisations->links('pagination::bootstrap-5')}}</div> --}}
            </div>
        </div>
        <!-- Table End -->
        <script>
            $(function() {
                $('#superOrganisation').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    ajax: {
                        url: '{{ route('superadmin.organisations.data') }}',
                        type: 'GET'
                    },
                    columns: [
                        { data: 'index', name: 'index', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'email', name: 'email' },
                        { data: 'phone', name: 'phone' },
                        { data: 'status', name: 'status' },
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
