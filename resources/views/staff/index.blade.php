@extends('dashboards.index')
@section('content')
    <style>
        .hover-bg-light:hover {
            background-color: rgba(0,0,0,0.05);
            transition: background-color 0.2s;
        }
    </style>
    <div>
        <!-- Sale & Revenue Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('document.received') }}">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 popout-effect">
                            <i class="fa fa-inbox fa-3x text-primary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Received Mails</p>
                                <h6 class="mb-0">{{ $recieved_documents_count }}</h6>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('document.sent') }}">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 popout-effect">
                            <i class="fa fa-paper-plane fa-3x text-primary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Treated Mails</p>
                                <h6 class="mb-0">{{ $sent_documents_count }}</h6>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('document.index') }}">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 popout-effect">
                            <i class="fa fa-chart-area fa-3x text-primary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Uploaded Files</p>
                                <h6 class="mb-0">{{ $uploaded_documents_count }}</h6>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <a href="#">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 popout-effect">
                            <i class="fa fa-chart-pie fa-3x text-primary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Total Feedback</p>
                                <h6 class="mb-0">0</h6>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <!-- Sale & Revenue End -->
        <!-- Widgets Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="h-100 bg-light rounded p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0">Recent Activity</h6>
                            <a href="">Show All</a>
                        </div>
                        @forelse ($activities as $activity)
                            <a href="{{ $activity->url }}" class="text-decoration-none text-dark d-block">
                                <div class="d-flex align-items-center border-bottom py-3 hover-bg-light">
                                    <img class="rounded-circle flex-shrink-0"
                                        src="{{ asset('uploads/avatars/' . ($activity->user->userDetail->avatar ?? $authUser->userDetail->avatar)) }}" alt=""
                                        style="width: 40px; height: 40px;">
                                    <div class="w-100 ms-3">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-0">{{ $activity->user->name }}</h6>
                                            <small>{{ $activity->created_at->diffForHumans() }}</small>
                                        </div>
                                        <span>{{ $activity->action }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="d-flex align-items-center border-bottom py-3">
                                <div class="w-100 ms-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-0">No Recent Activity</h6>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
        <!-- Widgets End -->
        {{-- @if ($activities->count() > 0)
            <div class="mt-4">
                {{ $activities->links('pagination::bootstrap-5') }}
            </div>
        @endif --}}
    </div>
@endsection
