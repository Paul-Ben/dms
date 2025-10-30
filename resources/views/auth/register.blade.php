@extends('layouts.logandregister')
@section('content')

    <div class="container text-center d-none d-sm-block">
        <div class="row no-wrap">
            <div class="col-4">
                <div class="p-3 "><img src="{{ asset('landing/images/hero_image1.png') }}" alt=""
                        style="border-radius: 1em;" width="220"></div>
            </div>
            <div class="col-4">
                <div class="p-3 "><img src="{{ asset('landing/images/landing_image_2.jpg') }}" alt=""
                        style="border-radius: 1em;" width="220"></div>
            </div>
            <div class="col-4">
                <div class="p-3 "><img src="{{ asset('landing/images/landing_image1.jpg') }}" alt=""
                        style="border-radius: 1em;" width="220"></div>
            </div>
        </div>
    </div>
    <div class="container form-layout ">
        <div class="row ">

            <div class="col-md-8 mx-auto ">
                <div class="contact-form mtop-small-150">

                    <div class="text-center"><img src="{{ asset('landing/images/benue_new_logo.svg') }}" width="100"
                            height="100" alt="benue_logo"></div>
                    <h2 class="mb-4 text-center"><span style="color: #0C4F24;">Register</span></h2>
                    <p class="sub-title py-3 text-center">Benue State Government Electronic Document Management System</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <!-- Name and Email Fields -->
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="floatingName" placeholder="Terver Ameh"
                                        name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                                    <label for="floatingName">Name</label>
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="floatingEmail"
                                        placeholder="name@example.com" name="email" value="{{ old('email') }}" required
                                        autocomplete="username">
                                    <label for="floatingEmail">Email address</label>
                                    @if ($errors->has('email'))
                                        <small class="text-danger">{{ $errors->first('email') }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Password Fields -->
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="floatingPassword" placeholder="Password"
                                        required name="password">
                                    <label for="floatingPassword">Password</label>

                                    <!-- Eye icon inside input -->
                                    <i class="bi bi-eye position-absolute top-50 end-0 translate-middle-y me-3"
                                        id="toggleFloatingPassword" style="cursor: pointer;"></i>
                                    {{-- <input type="password" class="form-control" id="floatingPassword" placeholder="Password"
                                        required name="password">

                                    <label for="floatingPassword">Password</label> --}}
                                    @if ($errors->has('password'))
                                        <small class="text-danger">{{ $errors->first('password') }}</small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="floatingConfirmPassword"
                                        placeholder="Confirm Password" required name="password_confirmation">
                                    <label for="floatingConfirmPassword">Confirm Password</label>
                                    <!-- Eye icon inside input -->
                                    <i class="bi bi-eye position-absolute top-50 end-0 translate-middle-y me-3"
                                        id="toggleFloatingPassword2" style="cursor: pointer;"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Phone and NIN Fields -->
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control" id="floatingPhone" placeholder="+1234567890"
                                        name="phone_number" style="padding: 16px;" value="{{ old('phone_number') }}"
                                        required pattern="^\+?[0-9\s\-()]{6,}$">
                                    <label for="floatingPhone">Phone Number</label>
                                    <input type="text" name="default_role" value="User " hidden>
                                    <input type="text" name="designation" value="User " hidden>
                                    <small id="phoneError" class="text-danger" style="display: none;">Invalid phone number
                                        for the selected country.</small>
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="floatingNIN"
                                        placeholder="Type your 11-digit NIN" name="nin_number"
                                        value="{{ old('nin_number') }}" required pattern="[0-9]{11}" maxlength="11">
                                    <label for="floatingNIN">NIN: Type your 11-digit NIN</label>
                                </div>
                            </div>
                        </div>

                        <!-- Gender and Account Type Fields -->
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-4">
                                    <select class="form-control" name="gender" id="genderSelect" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <label for="genderSelect">Gender</label>
                                    @if ($errors->has('gender'))
                                        <small class="text-danger">{{ $errors->first('gender') }}</small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-4">
                                    <select class="form-control" name="account_type" id="accountTypeSelect"
                                        onchange="toggleAccountFields()" required>
                                        <option value="">Select Account Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="corporate">Corporate</option>
                                    </select>
                                    <label for="accountTypeSelect">Account Type</label>
                                    @if ($errors->has('account_type'))
                                        <small class="text-danger">{{ $errors->first('account_type') }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Corporate Fields -->
                        <div id="corporateFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="floatingCompanyName"
                                            placeholder="Company Name" name="company_name"
                                            value="{{ old('company_name') }}">
                                        <label for="floatingCompanyName">Company Name</label>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="floatingRCNumber"
                                            placeholder="RC Number" name="rc_number" value="{{ old('rc_number') }}">
                                        <label for="floatingRCNumber">RC Number</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="floatingCompanyAddress"
                                            placeholder="Company Address" name="company_address"
                                            value="{{ old('company_address') }}">
                                        <label for="floatingCompanyAddress">Company Address</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Region Selection -->
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-4">
                                    <select class="form-control" name="region" id="regionSelect"
                                        onchange="toggleRegionFields()" required>
                                        <option value="">Select Region</option>
                                        <option value="nigeria">Nigeria</option>
                                        <option value="international">International</option>
                                    </select>
                                    <label for="regionSelect">Region</label>
                                    @if ($errors->has('region'))
                                        <small class="text-danger">{{ $errors->first('region') }}</small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-floating mb-4" id="internationalFields" style="display: none;">
                                    <select class="form-control" name="country" id="country">
                                        <option value="" selected='selected'>Select Country</option>
                                    </select>
                                    <label for="countryInput">Country</label>
                                    @if ($errors->has('country'))
                                        <small class="text-danger">{{ $errors->first('country') }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Nigeria Fields -->
                        <div id="nigeriaFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-floating mb-4">
                                        <select class="form-control" name="state" onchange="selectLGA(this)"
                                            id="state">
                                            <option value="" selected="selected">Select State</option>
                                        </select>
                                        <label for="stateSelect">State</label>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-floating mb-4">
                                        <select class="form-control" name="lga" id="lga">
                                            <option value="">Select Local Government Area</option>
                                        </select>
                                        <label for="lgaSelect">Local Government Area</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- International Fields -->

                        <div class="m-2 d-flex justify-content-center">
                            {!! NoCaptcha::display() !!}

                            @if ($errors->has('g-recaptcha-response'))
                                <div>
                                    <small class="text-danger">{{ $errors->first('g-recaptcha-response') }}</small>
                                </div>
                            @endif
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-center mb-4">
                            <button type="submit" class="btn btn-success py-3 w-50">Register</button>
                        </div>

                        <p class="text-center mb-0">Already have an Account? <a href="{{ route('login') }}">Sign In</a>
                        </p>
                    </form>
                    <div class="d-flex justify-content-center mt-2">
                        <p class="text-center sub-title">BENGEDMS, Powered by </p><a href="https://bdic.ng/"
                            target="__blank"><img src="{{ asset('landing/images/BDIC logo 1 1.svg') }}"></a>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- Include the intl-tel-input library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        var input = document.querySelector("#phone");
        var iti = window.intlTelInput(input, {
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@16.0.3/build/js/utils.js",
        });

        // store the instance variable so we can access it in the console e.g. window.iti.getNumber()
        window.iti = iti;

        (function($) {

            $('form').on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let form = $(this).get(0);
                $('form').addClass('was-validated');

                //Need to display invalid feedback explicitly on submit for input fields due to plugin changing structure
                $('form .tel-input').each(function() {
                    let telInput = $(this).get(0);
                    if ($(this).prop('required') && !telInput.checkValidity()) {
                        $(this).parents('.form-group').find('.invalid-feedback').show();
                    }
                });

            });

            input.addEventListener("countrychange", function() {
                let isValidNumber = iti.isValidNumber();
                checkPhoneValidation($(this), isValidNumber);
            });

            $('form').on('keyup', '.tel-input', function(event) {
                let isValidNumber = iti.isValidNumber();
                checkPhoneValidation($(this), isValidNumber);
            });

        })(jQuery);

        function checkPhoneValidation(element, isValidNumber) {

            let invFeedbackDiv = element.parents('.form-group').find('.invalid-feedback');
            input = element.get(0);

            if (isValidNumber) {
                //need to set this first as otherwise input validity is falsely linking to custom error
                input.setCustomValidity('');
            }

            if (isValidNumber && input.validity.valid) {
                input.setCustomValidity('');
                invFeedbackDiv.hide();
            } else {
                invFeedbackDiv.html('Invalid phone number');
                input.setCustomValidity('invalid');

                if ($('form').hasClass('was-validated')) {
                    invFeedbackDiv.show();
                }
            }

        }
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
        // Local-first Nigeria states/LGAs
        let NIGERIA_DATA = null; // { stateName: [lga, ...], ... }

        function populateStateSelect(states) {
            const stateSelect = document.getElementById('state');
            stateSelect.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.text = 'Select State';
            defaultOpt.value = '';
            stateSelect.add(defaultOpt);
            states.forEach((name) => {
                const option = document.createElement('option');
                option.text = name;
                option.value = name;
                stateSelect.add(option);
            });
        }

        async function loadNigeriaData() {
            try {
                const resp = await fetch('/data/nigeria-states-lgas.json', { cache: 'no-cache' });
                if (!resp.ok) throw new Error('Local Nigeria data not found');
                NIGERIA_DATA = await resp.json();
                const states = Object.keys(NIGERIA_DATA).sort((a, b) => a.localeCompare(b));
                populateStateSelect(states);
            } catch (e) {
                console.warn('Local Nigeria data missing, falling back to remote:', e);
                try {
                    const resp2 = await fetch('https://nga-states-lga.onrender.com/fetch');
                    if (!resp2.ok) throw new Error('Remote Nigeria states failed');
                    const remoteStates = await resp2.json();
                    populateStateSelect(remoteStates);
                } catch (e2) {
                    console.error('Failed to load Nigeria states from any source:', e2);
                    populateStateSelect([]);
                }
            }
        }

        // Populate LGAs from local first, remote fallback
        async function selectLGA(target) {
            const state = target.value;
            const lgaSelect = document.getElementById('lga');
            lgaSelect.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.text = 'Select Local Government Area';
            defaultOpt.value = '';
            lgaSelect.add(defaultOpt);

            // Try local
            if (NIGERIA_DATA && Array.isArray(NIGERIA_DATA[state]) && NIGERIA_DATA[state].length > 0) {
                NIGERIA_DATA[state]
                    .slice()
                    .sort((a, b) => a.localeCompare(b))
                    .forEach((name) => {
                        const option = document.createElement('option');
                        option.text = name;
                        option.value = name;
                        lgaSelect.add(option);
                    });
                return;
            }

            // Fallback to remote
            try {
                const resp = await fetch('https://nga-states-lga.onrender.com/?state=' + encodeURIComponent(state));
                if (!resp.ok) throw new Error('Remote LGAs failed');
                const remoteLGAs = await resp.json();
                for (let index = 0; index < Object.keys(remoteLGAs).length; index++) {
                    const option = document.createElement('option');
                    option.text = remoteLGAs[index];
                    option.value = remoteLGAs[index];
                    lgaSelect.add(option);
                }
            } catch (err) {
                const failOpt = document.createElement('option');
                failOpt.text = 'Failed to load LGAs. Please retry.';
                failOpt.value = '';
                lgaSelect.add(failOpt);
            }
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', loadNigeriaData);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure the dropdown element exists
            const countryDropdown = document.getElementById('country');
            if (!countryDropdown) {
                console.error('Dropdown element with ID "country" not found.');
                return;
            }

            function addDefaultOption(selectEl) {
                const defaultOption = document.createElement('option');
                defaultOption.text = 'Select Country';
                defaultOption.value = '';
                selectEl.add(defaultOption);
            }

            function populateCountriesFromRestCountries(items) {
                // Sort countries alphabetically by name
                items.sort((a, b) => a.name.common.localeCompare(b.name.common));
                items.forEach((country) => {
                    const option = document.createElement('option');
                    option.text = country.name.common;
                    option.value = country.name.common;
                    countryDropdown.add(option);
                });
            }

            function populateCountriesFromFirstOrg(dataObj) {
                const names = Object.values(dataObj).map((item) => item.country).filter(Boolean);
                names.sort((a, b) => a.localeCompare(b));
                names.forEach((name) => {
                    const option = document.createElement('option');
                    option.text = name;
                    option.value = name;
                    countryDropdown.add(option);
                });
            }

            async function loadCountries() {
                try {
                    // Try local first
                    const localResp = await fetch('/data/countries.json', { cache: 'no-cache' });
                    if (localResp.ok) {
                        const local = await localResp.json();
                        countryDropdown.innerHTML = '';
                        addDefaultOption(countryDropdown);
                        local
                            .slice()
                            .sort((a, b) => a.localeCompare(b))
                            .forEach((name) => {
                                const option = document.createElement('option');
                                option.text = name;
                                option.value = name;
                                countryDropdown.add(option);
                            });
                        return;
                    }

                    // Then RestCountries
                    const resp = await fetch('https://restcountries.com/v3.1/all');
                    if (!resp.ok) throw new Error('RestCountries failed: ' + resp.status + ' ' + resp.statusText);
                    const data = await resp.json();
                    countryDropdown.innerHTML = '';
                    addDefaultOption(countryDropdown);
                    populateCountriesFromRestCountries(data);
                } catch (err1) {
                    console.warn('RestCountries fetch failed, trying fallback:', err1);
                    try {
                        // Fallback to FIRST.org countries API
                        const resp2 = await fetch('https://api.first.org/data/v1/countries');
                        if (!resp2.ok) throw new Error('FIRST.org failed: ' + resp2.status + ' ' + resp2.statusText);
                        const json = await resp2.json();
                        countryDropdown.innerHTML = '';
                        addDefaultOption(countryDropdown);
                        populateCountriesFromFirstOrg(json.data || {});
                    } catch (err2) {
                        console.error('Error fetching countries:', err2);
                        const errorMessage = document.createElement('div');
                        errorMessage.textContent = 'Failed to load countries. Please try again later.';
                        errorMessage.style.color = 'red';
                        countryDropdown.parentElement.appendChild(errorMessage);
                    }
                }
            }

            loadCountries();
        });
    </script>
    <script>
        // Password field toggle
        const togglePassword = document.getElementById('toggleFloatingPassword');
        const passwordInput = document.getElementById('floatingPassword');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // Confirm Password field toggle
        const toggleConfirmPassword = document.getElementById('toggleFloatingPassword2');
        const confirmPasswordInput = document.getElementById('floatingConfirmPassword');

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>

@endsection
