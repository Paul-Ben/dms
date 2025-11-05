@extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
               
            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Sent Documents</h6>
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('document.file') }}">File New Document</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="userSent" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col" >#</th>
                            <th scope="col" style="width: 18.66%;" >Document No</th>
                            <th scope="col" style="width: 25.66%;" >Title</th>
                            <th scope="col">Sent To</th>
                            {{-- <th scope="col" style="width: 16.66%;">Comment</th> --}}
                            <th scope="col" >Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sent_documents as $key => $sent)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><a href="{{ route('document.view', $sent) }}">
                                    {{ $sent->document->docuent_number }}
                                </a>
                            </td>
                                <td>{{$sent->document->title}}</td>
                                <td>
                                    @if ($mda)
                                        {{$mda[0]->designation}}, <br>
                                    <span>{{$mda[0]->tenant->name}}</span>
                                    @else
                                    <span>Not Found</span>
                                    @endif
                                    
                                </td>
                                <td>{{$sent->document->created_at->format('M j, Y g:i A')}}</td>
                                {{-- <td>
                                    <a href="{{route('document.view_sent', $sent)}}" class="nav-item">View</a>
                                </td> --}}
                            </tr>
                            @empty
                            <tr class="text-center">
                                <td>0</td>
                                <td>No Data Found</td>
                                <td>—</td>
                                <td>—</td>
                                <td>—</td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
            {{-- @if($sent_documents->count() > 0)
            <div class="mt-3">
                {{$sent_documents->links('pagination::bootstrap-5')}}
            </div>
            @endif --}}
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(document).ready(function() {
            $('#userSent').DataTable({
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