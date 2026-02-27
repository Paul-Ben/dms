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
    
                            <form method="POST" action="{{route('document.store')}}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-12 col-xl-6 mb-3">
                                        <label for="exampleInputEmail1" class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="col-sm-12 col-xl-6 mb-3">
                                        <label for="exampleInputEmail1" class="form-label">Upload Document</label>
                                        <input type="file" name="file_path" class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-xl-6 mb-3" >
                                        <label for="exampleInputEmail1" class="form-label">Document Number</label>
                                        <input type="text" name="document_number" class="form-control" value="{{ 'EDMS/' . substr(Str::uuid(), 0, 6) . date('ymd')}}" placeholder="document number">
                                        <input type="text" name="uploaded_by" class="form-control" value="{{Auth::user()->id}}" placeholder="document number" hidden>
                                        <input type="text" name="department_id" class="form-control" value="{{Auth::user()->department_id}}" placeholder="document number" hidden>
                                        <input type="text" name="tenant_id" class="form-control" value="{{Auth::user()->tenant_id}}" placeholder="document number" hidden>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-xl-6 mb-3">
                                        <label for="exampleInputEmail1" class="form-label">Description</label>
                                            <textarea class="form-control" name="description" id="exampleInputEmail1" cols="30" rows="5"></textarea>
                                    </div>
                                    <div class="col-sm-12 col-xl-6 mb-3" hidden>
                                        <label for="exampleInputEmail1" class="form-label">Metadata</label>
                                            <textarea class="form-control" name="metadata" id="metadataField" cols="30" rows="5"></textarea>
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
        
        <script>
            // Example metadata object
            const metadata = {
                author: "John Doe",
                created_at: "2023-10-01",
                tags: ["important", "urgent"]
            };
        
            // Convert the metadata object to a JSON string and set it to the hidden input
            document.getElementById('metadataField').value = JSON.stringify(metadata);
        </script>
    </div>
@endsection

