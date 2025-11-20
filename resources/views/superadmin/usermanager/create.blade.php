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

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
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

                        </div>

                        <form method="POST" action="{{ route('user.save') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Email address</label>
                                    <input type="email" name="email" class="form-control" id="exampleInputEmail1"
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
                                    <input type="text" name="nin_number" class="form-control" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Phone</label>
                                    <input type="text" name="phone_number" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">User Role</label>
                                    <select name="default_role" class="form-select" required>
                                        <option selected>select role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect" class="form-label">Designation</label>
                                    <select id="designationSelect" name="designation" class="form-select" required>
                                        <option value="" selected>Select designation</option>
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
                                        class="form-select" required>
                                        <option value="" selected>Select organisation</option>
                                        @foreach ($organisations as $organisation)
                                            <option value="{{ $organisation->id }}">{{ $organisation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect" class="form-label">Department</label>
                                    <select id="departmentSelect" name="department_id" class="form-select" required>
                                        <option value="">Select department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="genderSelect" class="form-label">Gender</label>
                                    <select id="genderSelect" name="gender" class="form-select" required>
                                        <option value=" ">select menu</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="departmentSelect"
                                        class="form-label
                                    ">Signature</label>
                                    <input type="file" name="signature" id="signatureInput" class="form-control"
                                        accept="image/*">
                                </div>
                            </div>
                            <div class="row">

                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1"
                                        class="form-label
                                        ">PSN</label>
                                    <input type="text" name="psn" class="form-control" placeholder="PSN">
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1"
                                        class="form-label
                                        ">Grade Level</label>
                                    <input type="text" name="grade_level" class="form-control"
                                        placeholder="Grade Level">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1"
                                        class="form-label
                                        ">Rank</label>
                                    <input type="text" name="rank" class="form-control" placeholder="Rank">
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1"
                                        class="form-label
                                        ">Schedule</label>
                                    <input type="text" name="schedule" class="form-control" placeholder="Schedule">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1"
                                        class="form-label
                                        ">Employment Date</label>
                                    <input type="date" name="employment_date" class="form-control"
                                        placeholder="dd/mm/yyyy">
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1"
                                        class="form-label
                                        ">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control"
                                        placeholder="dd/mm/yyyy">
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <button type="submit" class="btn btn-primary">Create</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
        <!-- Form End -->
    </div>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJ+Y7B1b8v6Qn8zQeDk5Wv5xWc5Q5ZkN3E9Qc=" crossorigin="anonymous"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 searchable dropdowns
        (function() {
            const ready = (fn) => (document.readyState !== 'loading') ? fn() : document.addEventListener('DOMContentLoaded', fn);
            ready(function() {
                const $jq = window.jQuery || window.$;
                if (!$jq) {
                    console.error('jQuery not available: Select2 cannot initialize');
                    // Fallback: native searchable selects
                    makeNativeSearchable(document.getElementById('designationSelect'), 'Search designation');
                    makeNativeSearchable(document.getElementById('organisationSelect'), 'Search organisation');
                    document.getElementById('organisationSelect')?.addEventListener('change', function() { getDepartments(this.value); });
                    return;
                }
                if (typeof $jq.fn.select2 !== 'function') {
                    console.error('Select2 plugin not loaded');
                    // Fallback: native searchable selects
                    makeNativeSearchable(document.getElementById('designationSelect'), 'Search designation');
                    makeNativeSearchable(document.getElementById('organisationSelect'), 'Search organisation');
                    document.getElementById('organisationSelect')?.addEventListener('change', function() { getDepartments(this.value); });
                    return;
                }
                // Ensure containers size correctly
                $jq('#designationSelect').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select designation',
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
                $jq('#organisationSelect').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select organisation',
                    allowClear: true,
                    minimumResultsForSearch: 0
                });

                // Keep department loading in sync with Select2
                $jq('#organisationSelect').on('change', function() {
                    getDepartments(this.value);
                });
            });
        })();

        // Fallback: add a small input above select to filter options in-place
        function makeNativeSearchable(selectEl, placeholder) {
            if (!selectEl) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'mb-2';
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.placeholder = placeholder || 'Search';
            // Insert input right before the select
            selectEl.parentNode.insertBefore(wrapper, selectEl);
            wrapper.appendChild(input);
            wrapper.appendChild(selectEl);

            // Cache original options
            const original = Array.from(selectEl.options).map(opt => ({ value: opt.value, text: opt.text, selected: opt.selected }));
            input.addEventListener('input', function() {
                const q = this.value.trim().toLowerCase();
                const filtered = original.filter(o => !q || o.text.toLowerCase().includes(q));
                // Remember current selection
                const currentValue = selectEl.value;
                // Rebuild options
                while (selectEl.options.length) selectEl.remove(0);
                filtered.forEach(o => {
                    const opt = new Option(o.text, o.value, false, o.value === currentValue);
                    selectEl.add(opt);
                });
                // If current selection not present anymore, clear to placeholder if available
                if (!filtered.some(o => o.value === currentValue) && selectEl.options.length) {
                    selectEl.selectedIndex = 0;
                }
            });
        }

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
            departmentSelect.append('<option value="" selected>Select department</option>');

            $.each(data, function(key, value) {
                departmentSelect.append(`<option value="${value.id}">${value.name}</option>`);
            });
        }
    </script>

@endsection
