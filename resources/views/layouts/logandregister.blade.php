{{-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>BSGIDMS</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('favicon_io/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('favicon_io/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('favicon_io/favicon-16x16.png')}}">
    <link rel="manifest" href="{{asset('favicon_io/site.webmanifest')}}">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('dbf/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dbf/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css') }}" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('dbf/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('dbf/css/style.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <style>
        .iti {
            width: 100%;
            /* Ensure it takes full width */
            height: 100%;

        }

        .iti__flag-container {
            margin-right: 10px;
            /* Adjust spacing */
        }

        .iti__selected-flag {
            padding: 0 20px;
            /* Adjust padding */
        }

        .iti__arrow {
            border-top-color: #000;
            /* Match arrow color */
        }

        .iti__country-list {
            border-radius: 4px;
            /* Match border radius */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Add shadow */
        }
    </style>

    <!---- ReCaptcha ------>
    {!! htmlScriptTagJsApi() !!}

</head>

<body>
    <div>
        @yield('content')
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('dbf/lib/chart/chart.min.js') }}"></script>
    <script src="{{ asset('dbf/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('dbf/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('dbf/lib/owlcarousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('dbf/lib/tempusdominus/js/moment.min.js') }}"></script>
    <script src="{{ asset('dbf/lib/tempusdominus/js/moment-timezone.min.js') }}"></script>
    <script src="{{ asset('dbf/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('dbf/js/main.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- Include the intl-tel-input library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="{{ asset('dbf/js/custom.js') }}"></script>
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
    <script>
        function toggleAccountFields() {
            const accountType = document.getElementById('accountTypeSelect').value;
            const corporateFields = document.getElementById('corporateFields');

            if (accountType === 'corporate') {
                corporateFields.style.display = 'block';
            } else {
                corporateFields.style.display = 'none';
            }
        }

        function toggleRegionFields() {
            const region = document.getElementById('regionSelect').value;
            const nigeriaFields = document.getElementById('nigeriaFields');
            const internationalFields = document.getElementById('internationalFields');

            if (region === 'nigeria') {
                nigeriaFields.style.display = 'block';
                internationalFields.style.display = 'none';
            } else if (region === 'international') {
                nigeriaFields.style.display = 'none';
                internationalFields.style.display = 'block';
            } else {
                nigeriaFields.style.display = 'none';
                internationalFields.style.display = 'none';
            }
        }
    </script>
    <script>
        //Fetch all States
        fetch('https://nga-states-lga.onrender.com/fetch')
            .then((res) => res.json())
            .then((data) => {
                var x = document.getElementById("state");
                for (let index = 0; index < Object.keys(data).length; index++) {
                    var option = document.createElement("option");
                    option.text = data[index];
                    option.value = data[index];
                    x.add(option);
                }
            });
        //Fetch Local Goverments based on selected state
        function selectLGA(target) {
            var state = target.value;
            fetch('https://nga-states-lga.onrender.com/?state=' + state)
                .then((res) => res.json())
                .then((data) => {
                    var x = document.getElementById("lga");

                    var select = document.getElementById("lga");
                    var length = select.options.length;
                    for (i = length - 1; i >= 0; i--) {
                        select.options[i] = null;
                    }
                    for (let index = 0; index < Object.keys(data).length; index++) {
                        var option = document.createElement("option");
                        option.text = data[index];
                        option.value = data[index];
                        x.add(option);
                    }
                });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure the dropdown element exists
            const countryDropdown = document.getElementById('country');
            if (!countryDropdown) {
                console.error('Dropdown element with ID "country" not found.');
                return;
            }

            // Fetch countries from the API
            fetch('https://restcountries.com/v3.1/all')
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch countries: ' + response.statusText);
                    }
                    return response.json();
                })
                .then((data) => {
                    // Clear existing options (if any)
                    countryDropdown.innerHTML = '';

                    // Add a default option
                    const defaultOption = document.createElement('option');
                    defaultOption.text = 'Select Country';
                    defaultOption.value = '';
                    countryDropdown.add(defaultOption);

                    // Sort countries alphabetically by name
                    data.sort((a, b) => a.name.common.localeCompare(b.name.common));

                    // Populate the dropdown with country names
                    data.forEach((country) => {
                        const option = document.createElement('option');
                        option.text = country.name.common;
                        option.value = country.name.common;
                        countryDropdown.add(option);
                    });
                })
                .catch((error) => {
                    console.error('Error fetching countries:', error);
                    // Display a user-friendly error message
                    const errorMessage = document.createElement('div');
                    errorMessage.textContent = 'Failed to load countries. Please try again later.';
                    errorMessage.style.color = 'red';
                    countryDropdown.parentElement.appendChild(errorMessage);
                });
        });
    </script>
</body>

</html> --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benue State Government Integrated Document Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('landing/css/style.css') }}">
    <link rel="icon" href="{{ asset('landing/images/logo.jpeg') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('landing/images/logo.jpeg') }}" type="image/x-icon" />

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@100..800&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Sora:wght@100..800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
    <style>
        .iti {
            width: 100%;
            /* Ensure it takes full width */
            height: 100%;

        }

        .iti__flag-container {
            margin-right: 10px;
            /* Adjust spacing */
        }

        .iti__selected-flag {
            padding: 0 20px;
            /* Adjust padding */
        }

        .iti__arrow {
            border-top-color: #000;
            /* Match arrow color */
        }

        .iti__country-list {
            border-radius: 4px;
            /* Match border radius */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Add shadow */
        }
    </style>

    <!---- ReCaptcha ------>
    {!! NoCaptcha::renderJs() !!}

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
                <ul class="navbar-nav mb-2 mb-lg-0  ">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/#abt">About</a>
                    </li>

                    <li class="nav-item @if(request()->is('/contact')) active @endif">
                        <a class="nav-link @if(Route::is('contact')) active @endif" aria-current="page" href="{{route('contact')}}">Contact</a>
                    </li>
                </ul>

                <div class="ms-auto">
                    <ul class="navbar-nav ">
                        <div class="">
                            @if (Route::is('login'))
                                <a href="{{ route('register') }}">
                                    <button type="button" class="btn btn-success ">Register</button>
                                </a>
                            @else
                                <a href="{{ route('login') }}">
                                    <button type="button" class="btn btn-success ">Login</button>
                                </a>
                            @endif
                        </div>
                        <!-- <li class="nav-item "> <a class="nav-link "  href="#">Login</a> </li> -->
                    </ul>
                </div>

            </div>
        </div>
    </nav>

    <!-- Navigation End -->
    @yield('content')
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
                        Integrated Document Management System
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
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
