<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benue State Government Integrated Document Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">

    <style>
        .active {
            color: #0C4F24 !important;
        }

        .btn {

            width: 169px;
        }

        .navbar {
            background: white;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            height: 80px;
        }

        .navbar-brand {
            padding: 10px 20px;
            margin-bottom: 12px;
            right: 20px;
        }

        .navbar-toggle {
            border: none;
            background: transparent !important;
            margin-top: 25px;
            left: 40px;
        }

        .navbar-toggle .icon-bar {
            background: #30B34E !important;
            width: 25px;
            height: 4px;

        }

        .navbar-nav>li>a {
            margin-top: 12px;
            color: black !important;
        }

        .navbar-nav>li>a:hover {
            color: #30B34E !important;
        }

        @media (max-width: 767px) {
            .navbar-header {
                display: flex;
                justify-content: space-between;
                width: 100%;
                align-items: center;
            }

            .navbar-brand.hidden-lg {
                display: block !important;
                margin-right: 15px;
            }

            .navbar-brand.visible-lg {
                display: none !important;
            }

            .navbar-toggle {
                margin-left: auto;
                order: 2;
                align-self: center;
            }

            .navbar-collapse {
                background: #30B34E;
                position: absolute;
                width: 100%;
                top: 80px;
                z-index: 1000;
                padding: 10px 0;
                text-align: left !important;

            }

            .navbar-nav {
                width: 100%;
                text-align: center;
            }

            .navbar-nav>li {
                width: 100%;

            }

            .navbar-nav>li>a:hover {
                color: white !important;
            }
        }

        @media (min-width: 768px) {
            .navbar-brand.hidden-lg {
                display: none !important;
            }

            .navbar-brand.visible-lg {
                display: block !important;
            }
        }

        @media (max-width: 576px) {
            .navbar-collapse {
                background-color: #28a745;
                padding: 20px;
                text-align: left;
            }
        }


        @media (min-width: 577px) and (max-width: 992px) {
            .navbar-collapse {
                background-color: #28a745;
                padding: 20px;
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Start -->
    <nav class="navbar navbar-expand-lg  lg bg-body-tertiary">
        <div class="container">
            <a class="navbar-brand" href="#"><img src="{{ asset('landing/images/logo.jpeg') }}"
                    style="border-radius: 1em" alt="e-filling-logo" height="50"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('/') ? 'active-link' : '' }}" aria-current="page"
                            href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#abt">About</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>


                <ul class="navbar-nav ms-auto">
                    <div class="d-flex">
                        @if (Route::is('login'))
                            <a href="{{ route('register') }}">
                                <button type="button" class="btn btn-success">Register</button>
                            </a>
                        @elseif (Route::has('register'))
                            <a href="{{ route('login') }}">
                                <button type="button" class="btn btn-success ml-auto">Login</button>
                            </a>
                            @else
                            <a href="{{ route('login') }}">
                                <button type="button" class="btn btn-success ml-auto">Login</button>
                            </a>
                        @endif


                    </div>
                    <!-- <li class="nav-item "> <a class="nav-link "  href="#">Login</a> </li> -->
                </ul>

            </div>
        </div>
    </nav>
    <!-- Navigation End -->

    {{-- <div class="container">
        @yield('content')
    </div> --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('landing/js/script.js') }}"></script>
</body>

</html>
