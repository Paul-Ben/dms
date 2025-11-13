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
                    <tbody></tbody>
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
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('user.documents.sent.data') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'index', name: 'index', orderable: false },
                    { data: 'doc_no', name: 'documents.docuent_number' },
                    { data: 'title', name: 'documents.title' },
                    { data: 'sent_to', name: 'recipient_details' },
                    { data: 'date', name: 'file_movements.updated_at' },
                ],
                order: [[0, 'asc']],
                language: {
                    searchPlaceholder: "Search here...",
                    zeroRecords: "No matching records found",
                    lengthMenu: "Show entries",
                    infoFiltered: "(filtered from MAX total entries)",
                }
            });
        });
    </script>
@endsection