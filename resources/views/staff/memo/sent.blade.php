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
                <h6 class="mb-0">Outgoing Memos</h6>
                <div>
                    {{-- <a class="btn btn-sm btn-primary" href="{{ route('document.create') }}">Add Document</a> --}}
                    <a class="btn btn-sm btn-primary" href="{{ route('memo.index') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="staffMemoSent" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col" >#</th>
                            <th scope="col" >Document No</th>
                            <th scope="col" >Subject</th>
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
            $('#staffMemoSent').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('staff.memo.sent.data') }}',
                    type: 'GET'
                },
                columns: [
                    { data: 'index', name: 'index', orderable: false, searchable: false },
                    { data: 'doc_no', name: 'memos.docuent_number' },
                    { data: 'subject', name: 'memos.title' },
                    { data: 'sent_to', name: 'recipient_details' },
                    { data: 'date', name: 'memo_movements.updated_at' }
                ],
                order: [[4, 'desc']],
                lengthMenu: [10, 25, 50, 100],
                language: {
                    searchPlaceholder: 'Search here...',
                    zeroRecords: 'No matching records found',
                    lengthMenu: 'Show entries'
                }
            });
        });
    </script>
@endsection