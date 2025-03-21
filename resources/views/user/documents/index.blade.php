@extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
               @if (session('success'))
               <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>{{session('success')}}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
               @endif
               <script>
                @if(session()->has('toastr'))
                    {!! session('toastr') !!}
                @endif
            </script>
            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Document Management</h6>
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('document.file') }}">File New Document</a>
                    {{-- <a class="btn btn-sm btn-primary" href="{{ route('document.create') }}">Add Document</a> --}}
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Document No</th>
                            <th scope="col">Title</th>
                            {{-- <th scope="col">Uploaded By</th> --}}
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $key => $document)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><a target="_blank" href="{{asset($document->file_path)}}">{{$document->docuent_number}}</a></td>
                                <td>{{$document->title}}</td>
                                {{-- <td></td> --}}
                                <td>Processing</td>
                                <td>
                                    <div class="nav-item">
                                        <a target="_blank" href="{{asset($document->file_path)}}" class="nav-link">View</a>
                        
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="text-center">
                                <td colspan="6">No Data Found</td>
                                </tr>
                        @endforelse

                    </tbody>
                </table>
                <div class="pt-4">
                    {{$documents->links('pagination::bootstrap-5')}}
                </div>
                
            </div>
        </div>
    </div>
    <!-- Table End -->
@endsection
