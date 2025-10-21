<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benue State Government Electronic Document Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('landing/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('landing/css/style2.css') }}">
    <link rel="icon" href="{{ asset('landing/images/logo.jpeg') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ 'landing/images/logo.jpeg' }}" type="image/x-icon" />

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@100..800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Sora:wght@100..800&display=swap"
        rel="stylesheet">

    <style>


    </style>
</head>

<body>
    <!-- Navigation Start -->
    <nav class="navbar navbar-expand-lg  lg bg-body-tertiary ">
        <div class="container ">
            <a class="navbar-brand" href="#"><img src="{{ asset('landing/images/benue_new_logo.svg') }}"
                    style="border-radius: 1em" alt="e-filling-logo" height="50"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mb-2 mb-lg-0  " >
                    {{-- <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ url('/') }}">Home</a>
                    </li> --}}
                    <li class="nav-item @if(request()->is('/')) active @endif">
                        <a class="nav-link @if(request()->is('/')) active @endif" aria-current="page" href="{{ url('/') }}">Home</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="/#abt">About</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link @if(request()->is('/contact')) active @endif" aria-current="page" href="{{route('contact')}}">Contact</a>
                    </li>
                </ul>

                <div class="ms-auto">
                    <ul class="navbar-nav ">
                        <div class="">
                            @auth
                                <div class="d-flex flex-wrap justify-content-center mb-2">
                                    <a href="{{ route('dashboard') }}" class="btn btn-success me-2 mb-2">Dashboard</a>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-danger mb-2">Logout</button>
                                    </form>
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-success">Login</a>
                            @endauth

                        </div>
                        <!-- <li class="nav-item "> <a class="nav-link "  href="#">Login</a> </li> -->
                    </ul>
                </div>

            </div>
        </div>
    </nav>

    <!-- Navigation End -->
    @yield('content')
    <!-- ========================== -->
    <!-- ====================================== -->
    <!-- Footer start -->
    <div class="row  text-white text-center">
        <div class="footer-container">
            <div class="row mt-4">
                <!-- First row, three columns -->

                <div class="col-md-4 p-5 text-center">

                    <!-- <div class="d-flex align-items-center text-center" style="margin-left:40%">
                        <img src="images/SEAL BENUE STATE GOV 1.svg" style="padding-right: 5px;">
                        <p style="text-align: left;">Benue State Government
                            Integrated Document Management System</p>
                    </div> -->
                    <div class="d-flex text-start">
                        <a href="#" class="text-light me-2"><img
                                src="{{ asset('landing/images/benue_new_logo.svg') }}" width="65"></a> Benue
                        State Government
                        Electronic Document Management System
                    </div>
                </div>



                <div class="col-md-4 p-4">


                    <h5>Quick Links</h5>
                    <a href="{{ url('/') }}" class="text-light me-3">Home</a>
                    <a href="#abt" class="text-light me-3">About us</a>
                    <a href="{{route('contact')}}" class="text-light me-3">Contact us</a>
                    <a href="{{ route('login') }}" class="text-light">Login</a>

                    <!-- <div class="row">
                        <div class="col-md-3">
                            <a href="#" class="text-white">Home</a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="text-white">About us</a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="text-white">Contact us</a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="text-white">Login</a>
                        </div>
                    </div> -->

                </div>
                <div class="col-md-4 p-4">
                    <!-- <div class="row text-center" style="text-align: center; padding: 2%;">
                        <div class="col-md-3">
                            <img src="images/1.svg" >
                        </div>
                        <div class="col-md-3">
                            <img src="images/2.svg" >
                        </div>
                        <div class="col-md-3">
                            <img src="images/3.svg" >
                        </div>
                        <div class="col-md-3">
                            <img src="images/4.svg" >
                        </div>
                    </div> -->

                    <h5>Follow Us</h5>
                    <a href="#" class="text-light me-3"><img src="{{ asset('landing/images/1.svg') }}"></a>
                    <a href="#" class="text-light me-3"><img src="{{ asset('landing/images/2.svg') }}"></a>
                    <a href="#" class="text-light me-3"><img src="{{ asset('landing/images/3.svg') }}"></a>
                    <a href="#" class="text-light"><img src="{{ asset('landing/images/4.svg') }}"></a><br><br>
                    <span class="body-text">
                        <a href="#" class="text-light me-3">Privacy Policy</a>
                        <a href="#" class="text-light me-3">Terms & Conditions</a>
                    </span>
                </div>
            </div>

            <hr>

            <div class="row mt-4 p-4">

                <div class="col-md-4">
                    &nbsp;
                </div>
                <div class="col-md-4">
                    <p>© Copyright 2025, All Rights Reserved</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center text-center" style="margin-left:40%">
                        <p style="padding-right: 5px;">Powered by BDIC</p><a href="https://bdic.ng/" target="__blank"><img
                            src="{{ asset('landing/images/BDIC logo 1 1.svg') }}"></a>

                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Footer end -->
    <script src="{{ asset('landing/js/script.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js "></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        @if (Session::has('message'))
            var type = "{{ Session::get('alert-type', 'info') }}"
            switch (type) {
                case 'info':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.info("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();
                    break;
                case 'success':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.success("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();

                    break;
                case 'warning':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.warning("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();

                    break;
                case 'error':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.error("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();

                    break;
            }
        @endif
    </script>
</body>

</html>
