@extends('dashboards.index')
@section('content')
    <div>
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
        <!-- Button End h-100 -->
        <!-- Form Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="bg-light rounded  p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h6 class="mb-4">Fill All Fields Required</h6>
                            <div>
                                <a class="btn btn-sm btn-primary" href="{{ route('users.index') }}"><i
                                        class="fa fa-arrow-left me-2"></i>Back</a>
                            </div>
                            @if (session('errors'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fa fa-exclamation-circle me-2"></i>{{ session('errors') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                                </div>
                                
                            @endif

                        </div>

                        <form method="POST" action="{{ route('user.update', $user) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Name</label>
                                    <input type="text" value="{{$user->name}}" name="name" class="form-control" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Email address</label>
                                    <input type="email" value="{{$user->email}}" name="email" class="form-control" id="exampleInputEmail1"
                                        aria-describedby="emailHelp" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Password</label>
                                    <input type="password" value="{{$user->password}}" name="password" class="form-control" id="exampleInputEmail1"
                                        aria-describedby="emailHelp" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">NIN</label>
                                    <input type="text" value="{{$user_details->userDetail->nin_number}}" name="nin_number" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Phone</label>
                                    <input type="text" value="{{$user_details->userDetail->phone_number}}" name="phone_number" class="form-control">
                                </div>
                                 <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Avatar</label>
                                    <input type="file" value="{{$user_details->userDetail->avatar}}" name="avatar" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                               
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">User Role</label>
                                    <select name="default_role" class="form-select">
                                        <option value="{{$user->default_role}}">{{$user->default_role}}</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect" class="form-label">Designation</label>
                                    <select id="designationSelect" name="designation" class="form-select">
                                        <option value="{{$user_details->userDetail->designation}}">{{$user_details->userDetail->designation}}</option>
                                        @foreach ($designations as $designation)
                                            <option value="{{ $designation->name }}">{{ $designation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Organisation</label>
                                    <select id="organisationSelect" name="tenant_id" onchange="getDepartments(this.value)"
                                        class="form-select">
                                        <option value="{{$user_details->userDetail->tenant_id}}">{{$user_details->userDetail->tenant_id}}</option>
                                        @foreach ($organisations as $organisation)
                                            <option value="{{ $organisation->id }}">{{ $organisation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect" class="form-label">Department</label>
                                    <select id="departmentSelect" name="department_id" class="form-select">
                                        <option value="{{$user_details->userDetail->department_id}}">{{$user_details->userDetail->department_id}}</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="genderSelect" class="form-label">Gender</label>
                                    <select id="genderSelect" name="gender" class="form-select">
                                        <option value="{{$user_details->userDetail->gender}}">{{$user_details->userDetail->gender}}</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect"
                                        class="form-label
                                    ">Signature</label>
                                    <input type="text" value="{{$user_details->userDetail->signature}}" name="signature" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                
                            </div>
                            <div style="text-align: center;">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
        <!-- Form End -->
    </div>

    <script>
        function getDepartments(organisationId) {
            const departmentSelect = $('#departmentSelect');

            // Clear the dropdown and show loading indicator
            // departmentSelect.empty();
            // departmentSelect.append('<option selected>Loading...</option>');

            if (organisationId) {
                $.ajax({
                    url: `/dashboard/get-departments/${organisationId}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        console.log('Departments:', data);
                        populateDepartmentSelect(data);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching departments:', error);
                        departmentSelect.empty();
                        departmentSelect.append('<option selected>Error loading departments</option>');
                    }
                });
            } else {
                departmentSelect.empty();
                departmentSelect.append('<option selected>Select menu</option>');
            }
        }

        function populateDepartmentSelect(data) {
            const departmentSelect = $('#departmentSelect');
            departmentSelect.empty();
            departmentSelect.append('<option selected>Select menu</option>');

            $.each(data, function(key, value) {
                departmentSelect.append(`<option value="${value.id}">${value.name}</option>`);
            });
        }
    </script>
@endsection
