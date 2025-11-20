@extends('dashboards.index')
@section('content')
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
                    <div class="mb-3">
                        <a href="{{ asset('templates/Template_sample.csv') }}" class="btn btn-success" download>
                            <i class="fa fa-download"></i> Download CSV Template
                        </a>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted">User Upload Instructions:</p>
                        <ul>
                            <li>Download the template above and use to maintain the correct structure.</li>
                            <li>Ensure the CSV file is formatted correctly.</li>
                            <li>Use the correct headers as per the template (first row are column names).</li>
                            <li>Ensure that the Organisation (Tenant) and Departments exist in the system.</li>
                            <li>Check for any duplicate emails to avoid conflicts.</li>
                        </ul>
                    </div>
                    {{-- Flash & Validation Alerts --}}
                    @if(session('message'))
                        <div class="alert alert-{{ session('alert-type', 'info') }} alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i>{{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Upload failed:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('row_errors') && is_array(session('row_errors')))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Row processing errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach (session('row_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('userUpload.csv') }}" method="POST" enctype="multipart/form-data" id="csvUploadForm">
                        @csrf
                        <div class="row">
                            <div class="col-sm-12 col-xl-6 mb-3">
                                <label class="form-label" for="csv_file">Choose CSV File:</label>
                                <input class="form-control" type="file" name="csv_file" id="csv_file" accept=".csv"
                                    required>
                                <div class="mt-3 d-flex align-items-center gap-2">
                                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                                        <span class="spinner-border spinner-border-sm me-2 d-none" id="uploadSpinner" role="status" aria-hidden="true"></span>
                                        <span id="uploadBtnText">Upload</span>
                                    </button>
                                    <button type="reset" class="btn btn-secondary">Reset</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <script>
                        (function() {
                            const form = document.getElementById('csvUploadForm');
                            const btn = document.getElementById('uploadBtn');
                            const spinner = document.getElementById('uploadSpinner');
                            const btnText = document.getElementById('uploadBtnText');

                            if (form && btn && spinner && btnText) {
                                form.addEventListener('submit', function() {
                                    btn.disabled = true;
                                    spinner.classList.remove('d-none');
                                    btnText.textContent = 'Uploading...';
                                });
                            }
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection
