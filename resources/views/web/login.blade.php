@extends('web.layouts.app')
@section('title', 'Welcome to EliteGrade')

@section('css')
    <style>
        header.float-start.w-100 {
            position: absolute;
            z-index: 10;
        }
    </style>
@endsection
@section('content')
    <div class="about_us_section_new float-start w-100">
        <div class="col-lg-7 col-md-12 col-sm-12 col-xs-12">
            <div class="login_form_main">
                <!--	<a href="index.html" class="back_btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>  Back to Home</a>-->
                <div class="contact-us-section">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <div class="enquay-form-section bg-white">
                                <h3 style="color: #000; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 1px;"
                                    class="mb-2">Login </h3>
                                <p class="mb-5 text-center">Please sign-in to your account and start the adventure.</p>
                                <div class="form-container">
                                    <div class="step active">
                                        <div class="custom-select">
                                            <div class="select-box d-flex form-control w-100">
                                                <span class="selected">Select option</span>
                                                <span class="arrow">&#9662;</span>
                                            </div>

                                            <div class="options">
                                                <div class="option">Teacher</div>
                                                <div class="option">Student</div>
                                                <div class="option">Parent</div>
                                            </div>
                                        </div>
                                        <button type="button" id="next-step-btn" class="btn-main w-100">Next</button>
                                    </div>

                                    <div class="step">
                                        <form id="login-form" class="form-horizontal" action="{{ route('web.login.submit') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="role" id="login-role" value="teacher">
                                            <div class="form-group mb-3 ajax-msg"></div>
                                            <div class="form-group mb-3 ajax-field">
                                                <input type="text" class="form-control" id="email"
                                                    placeholder="Email" name="email">
                                                <span class="ajax-error text-danger small"></span>
                                            </div>
                                            <div class="form-group mb-3 ajax-field">
                                                <div class="password-wrapper">
                                                    <input type="password" id="password" placeholder="Enter password"
                                                        class="form-control" name="password">

                                                    <img id="togglePassword"
                                                        src="https://img.icons8.com/ios-glyphs/30/000000/visible.png"
                                                        alt="show">
                                                </div>
                                                <span class="ajax-error text-danger small"></span>
                                            </div>
                                            <div class="form-group mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" id="remember" name="remember" value="1" class="me-2">
                                                    <label class="mb-0" for="remember_me">Remember me</label>
                                                </div>
                                                <a href="{{ url('forgot-password') }}">Forgot password?</a>
                                            </div>
                                            <div class="form-group mb-3 d-flex align-items-start ajax-field">
                                                <input type="checkbox" id="terms" name="terms" class="me-2 mt-1">
                                                <label class="mb-0" for="terms">I agree to the <a href="">terms and conditions</a></label>
                                                <span class="ajax-error text-danger small"></span>
                                            </div>
                                            <!--  <div class="form-group mb-3 float-start w-100 text-box"> <span class="text_continue bg-card px-2 text-muted-foreground text-uppercase text-center">Or continue with</span> </div>-->
                                            <!--<div class="form-group mb-3 float-start w-100">
                    <button class="w-full flex google-btn items-center justify-center gap-3 p-4 rounded-2xl bg-card border border-border hover:bg-muted transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"></path>
                      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"></path>
                      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"></path>
                      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"></path>
                    </svg>
                    <span class="font-medium text-foreground">Google</span> </button>
                  </div>
                  <div class="form-group mb-3 float-start w-100">
                    <p class="text-center">Don't have account ? <a href="sign-up.html" class="register_btn">Create an account</a></p>
                  </div>-->
                                        </form>
                                        <button type="button" id="prev-step-btn" class="back-btn w-100"><img width="13"
                                                height="13" src="https://img.icons8.com/ios-filled/100/back.png"
                                                alt="back" /></button>
                                        <button type="button" id="submit-login-btn" class="btn-main w-100 submit-button">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <div class="login_main">
                                <img src="{{ url('public/web_theme/assets/images/login.png') }}" alt="" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script>
        var currentStep = 0;
        const steps = document.querySelectorAll(".step");

        function clearAjaxErrors() {
            $('#login-form .ajax-error').text('');
            $('#login-form .ajax-msg').html('');
        }

        function handleAjaxResponse(res) {
            if (res.status == 1) {
                if (res.redirect_url) {
                    window.location.href = res.redirect_url;
                    return;
                }
                $('#login-form .ajax-msg').html('<div class="alert alert-success">' + (res.msg || 'Success') + '</div>');
                return;
            }

            if (res.error_array) {
                Object.keys(res.error_array).forEach(function(key) {
                    const field = $('#login-form [name="' + key + '"]');
                    if (field.length) {
                        field.closest('.ajax-field').find('.ajax-error').text(res.error_array[key]);
                    }
                });
            }

            if (res.error) {
                $('#login-form .ajax-msg').html('<div class="alert alert-danger">' + res.error + '</div>');
            }
        }

        $(document).on('submit', '#login-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();

            const _this = $(this);
            _this.find('.submit-button').attr('disabled', 'disabled').text('Please wait...');

            $.post(_this.attr('action'), _this.serializeArray(), function(res) {
                _this.find('.submit-button').removeAttr('disabled').text('Submit');
                handleAjaxResponse(res);
            }, 'json');
        });

        function showStep(index) {
            steps.forEach((step, i) => {
                step.classList.toggle("active", i === index);
            });
        }

        function nextStep() {
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function submitForm() {
            alert("Form submitted!");
        }

        // Initialize
        showStep(currentStep);

        document.getElementById('next-step-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            nextStep();
        });

        document.getElementById('prev-step-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            prevStep();
        });

        document.getElementById('submit-login-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            $('#login-form').trigger('submit');
        });
    </script>

    <script>
        const selectBox = document.querySelector(".select-box");
        const customSelect = document.querySelector(".custom-select");
        const options = document.querySelectorAll(".option");
        const selected = document.querySelector(".selected");

        selectBox.addEventListener("click", () => {
            customSelect.classList.toggle("active");
        });

        options.forEach(option => {
            option.addEventListener("click", () => {
                const roleText = option.textContent.trim().toLowerCase();
                selected.textContent = option.textContent;
                document.getElementById("login-role").value = roleText;
                customSelect.classList.remove("active");
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            if (!customSelect.contains(e.target)) {
                customSelect.classList.remove("active");
            }
        });
    </script>

    <script>
        const password = document.getElementById("password");
        const toggle = document.getElementById("togglePassword");

        toggle.addEventListener("click", function() {
            if (password.type === "password") {
                password.type = "text";
                toggle.src = "https://img.icons8.com/ios-glyphs/30/000000/invisible.png";
            } else {
                password.type = "password";
                toggle.src = "https://img.icons8.com/ios-glyphs/30/000000/visible.png";
            }
        });
    </script>
@endsection
