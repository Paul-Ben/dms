@extends('dashboards.index')
@section('content')
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
            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Memo Management</h6>
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('memo.create') }}">Memo</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('memo.template') }}">Add Template</a>
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="fa fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="staffMemos" class="table text-start align-middle table-bordered table-hover mb-0">
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
                    <tbody></tbody>
                </table>
                <div class="pt-4">
                    {{-- {{ $memos->links('pagination::bootstrap-5') }} --}}
                </div>
            </div>
        </div>
    </div>
    <!-- Pop-up Modal -->
    <div id="sendOptionsModal" class="modal pt-5" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Choose Sending Option</h2>
            <p>Would you like to send the document internally or externally?</p>
            <button onclick="sendDocument('internal')">Send Internally</button><br>
            <button onclick="sendDocument('external')">Send Externally</button>
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(document).ready(function() {
            $('#staffMemos').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('staff.memo.index.data') }}',
                    type: 'GET'
                },
                columns: [
                    { data: 'index', name: 'index', orderable: false, searchable: false },
                    { data: 'doc_no', name: 'memos.docuent_number' },
                    { data: 'title', name: 'memos.title' },
                    { data: 'status', name: 'memos.status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                lengthMenu: [10, 25, 50, 100],
                language: {
                    searchPlaceholder: 'Search here...',
                    zeroRecords: 'No matching records found',
                    lengthMenu: 'Show entries'
                }
            });
        });
    </script>
    
    <style>
        .modal {
            display: flex;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 300px;
        }

        .close {
            color: #a31212;
            float: right;
            font-size: 28px;
        }

        .close:hover,
        .close:focus {
            color: rgb(198, 63, 63);
            text-decoration: none;
            cursor: pointer;
        }
    </style>
@endsection
