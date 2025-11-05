@extends('dashboards.index')
@section('content')
    <div>

        <!-- Button Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="col-12">
                <div class="bg-light rounded h-100 p-4">
                    @if (session('errors'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">

                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
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
                            <div>
                                <h6 class="mb-4">Create a Document Record</h6>
                                <p>Fill All Fields Required</p>
                            </div>
                            <div>
                                <a class="btn btn-sm btn-primary" href="{{ route('document.index') }}"><i
                                        class="fa fa-arrow-left me-2"></i>Back</a>
                            </div>

                        </div>

                        <form method="POST" action="{{ route('document.storefile') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Recipient Organisation</label>
                                    <select class="form-select" name="recipient_id" required>
                                        <option value="">Select Recipient</option>
                                        @foreach ($recipients as $recipient)
                                            <option value="{{ $recipient['admin_id'] }}">{{ $recipient['tenant_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Document Number</label>
                                    <input type="text" name="document_number" class="form-control"
                                        value="{{ 'BN/doc/' . rand(0, 9999) . date('YmdHis') }}"
                                        placeholder="document number" readonly>
                                    <input type="text" name="uploaded_by" class="form-control"
                                        value="{{ Auth::user()->id }}" placeholder="document number" hidden>
                                    <input type="text" name="department_id" class="form-control"
                                        value="{{ Auth::user()->department_id }}" placeholder="document number" hidden>
                                    <input type="text" name="tenant_id" class="form-control"
                                        value="{{ Auth::user()->tenant_id }}" placeholder="document number" hidden>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Upload Document, <span>PDF file 10MB allowed.</span></label>
                                    <input type="file" name="file_path" id="fileInput" class="form-control" 
                                        accept=".pdf">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="exampleInputEmail1" cols="30" rows="5"></textarea>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3">
                                    <div id="previewContainer">
                                        <img id="imagePreview" style="display: none; max-width: 100%; max-height: 400px;" />
                                        <iframe id="pdfPreview" style="display: none; width: 100%; height: 400px;"
                                            frameborder="0"></iframe>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-xl-6 mb-3" hidden>
                                    <label for="exampleInputEmail1" class="form-label">Metadata</label>
                                    <textarea class="form-control" name="metadata" id="metadataField" cols="30" rows="5"></textarea>

                                </div>
                            </div>
                            <div style="text-align: center;">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                {{-- <button type="reset" class="btn btn-secondary">Reset</button> --}}
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        <!-- Form End -->

        <script>
            document.getElementById("fileInput").addEventListener("change", function() {
                var file = this.files[0];
                if (file && file.type !== "application/pdf") {
                    alert("Only PDF files are allowed!");
                    this.value = ""; // Clear the file input
                }
            });
            // Example metadata object
            const metadata = {
                author: "John Doe",
                created_at: "2023-10-01",
                tags: ["important", "urgent"]
            };

            // Convert the metadata object to a JSON string and set it to the hidden input
            document.getElementById('metadataField').value = JSON.stringify(metadata);
        </script>
        <script>
            // Get the file input element
            document.getElementById('fileInput').addEventListener('change', function(event) {
                const file = event.target.files[0];
                const previewContainer = document.getElementById('previewContainer');
                const imagePreview = document.getElementById('imagePreview');
                const pdfPreview = document.getElementById('pdfPreview');

                // Clear previous previews
                imagePreview.style.display = 'none';
                pdfPreview.style.display = 'none';
                imagePreview.src = '';
                pdfPreview.src = '';

                if (file) {
                    const fileType = file.type;

                    if (fileType.startsWith('image/')) {
                        // Display image preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else if (fileType === 'application/pdf') {
                        // Display PDF preview
                        const fileURL = URL.createObjectURL(file);
                        pdfPreview.src = fileURL;
                        pdfPreview.style.display = 'block';
                    } else {
                        alert('Unsupported file type. Please upload an image or a PDF.');
                    }
                }
            });
        </script>
    </div>
@endsection
