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
                <h6 class="mb-0">Received Documents</h6>
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('document.file') }}">File New Document</a>
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="userReceived" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Document No</th>
                            <th scope="col">Title</th>
                            <th scope="col">Sent By</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(document).ready(function() {
            $('#userReceived').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('user.documents.received.data') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'index', name: 'index', orderable: false },
                    { data: 'doc_no', name: 'documents.docuent_number' },
                    { data: 'title', name: 'documents.title' },
                    { data: 'sent_by', name: 'sender.name' },
                    { data: 'status', name: 'documents.status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
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