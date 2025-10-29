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
                    <tbody>
                        @foreach ($users as $key => $user)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><a href="{{route('user.view', $user)}}">{{ $user->name }}</a></td>
                                <td>{{ $user->userDetail->tenant->name }}</td>

                                <td>{{ $user->userDetail?->tenant_department?->name ?? $user->userDetail->designation }}</td>
                                <td>
                                    <div class="nav-item dropdown">
                                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>
                                        <div class="dropdown-menu">
                                            <a href="{{route('user.edit', $user)}}" class="dropdown-item">Edit</a>
                                            @if($user->is_active)
                                                <form method="POST" action="{{ route('user.deactivate', $user->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this user? They will not be able to log in until reactivated.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item text-warning" style="border: none; background: none; text-align: left; width: 100%;">Deactivate</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('user.activate', $user->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to activate this user?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item text-success" style="border: none; background: none; text-align: left; width: 100%;">Activate</button>
                                                </form>
                                            @endif

                                            @if(is_null($user->email_verified_at))
                                                <form method="POST" action="{{ route('user.verify', $user->id) }}" style="display: inline;" onsubmit="return confirm('Verify this account?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item" style="border: none; background: none; text-align: left; width: 100%;">Verify Account</button>
                                                </form>
                                            @else
                                                <span class="dropdown-item text-muted">Verified</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
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
        $(document).ready(function() {
            $('#superUserMan').DataTable({
                responsive: true,
                autoWidth: false,
                paging: true, // Enable pagination
                searching: true, // Enable search
                ordering: true, // Enable sorting
                lengthMenu: [10, 25, 50, 100], // Dropdown for showing entries
                columnDefs: [{
                        orderable: false,
                        targets: -1
                    } // Disable sorting on last column (Actions)
                ],
                language: {
                    searchPlaceholder: "Search here...",
                    zeroRecords: "No matching records found",
                    lengthMenu: "Show entries",
                    // info: "Showing START to END of TOTAL entries",
                    infoFiltered: "(filtered from MAX total entries)",
                }
            });
        });


    </script>
@endsection
