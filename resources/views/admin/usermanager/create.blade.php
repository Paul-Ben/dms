@extends('dashboards.index')
@section('content')
    <div>
        <!-- Button Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="col-12">
                <div class="bg-light rounded h-100 p-4">

                </div>
            </div>
        </div>
        <!-- Button End -->
        <!-- Form Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h6 class="mb-4">Fill All Fields Required</h6>
                            <div>
                                <a class="btn btn-sm btn-primary" href="{{ route('users.index') }}"><i
                                        class="fa fa-arrow-left me-2"></i>Back</a>
                            </div>

                        </div>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Name</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="exampleInputEmail1"
                                        aria-describedby="emailHelp" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" id="exampleInputEmail1"
                                        aria-describedby="emailHelp" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Confirm Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">NIN</label>
                                    <input type="text" name="nin_number" class="form-control">
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Avatar</label>
                                    <input type="file" name="avatar" class="form-control">
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">User Role</label>
                                    <select name="default_role" class="form-select">
                                        <option selected>select menu</option>
                                        <option value="admin">Admin</option>
                                        <option value="user">User</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Organisation</label>
                                    <select id="organisationSelect" name="tenant_id" onchange="getDepartment()"
                                        class="form-select">
                                        <option selected>select menu</option>
                                        @foreach ($organisations as $organisation)
                                            <option value="{{ $organisation->id }}">{{ $organisation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect" class="form-label">Department</label>
                                    <select id="departmentSelect" name="department_id" class="form-select">
                                        <option selected>select menu</option>

                                    </select>
                                </div>
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
        function getDepartment() {
            document.getElementById('organisationSelect').addEventListener('change', function() {
                var organisationId = this.value;
                
                // Clear the department dropdown
                var departmentSelect = document.getElementById('departmentSelect');
                departmentSelect.innerHTML = '<option selected>select menu</option>'; // Reset options
    
                if (organisationId) {
                    // Make an AJAX call to fetch departments
                    fetch(`/get-departments/${organisationId}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(department => {
                                var option = document.createElement('option');
                                option.value = department.id;
                                option.textContent = department.name;
                                departmentSelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Error fetching departments:', error));
                }
            });
        }
    </script>
@endsection