@extends('web.layouts.app')
@section('title', 'Forgot password | EliteGrade')

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
                <div class="contact-us-section">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <div class="enquay-form-section bg-white">
                                <h3 style="color: #000; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 1px;"
                                    class="mb-2">Forgot password</h3>
                                <p class="mb-5 text-center">Enter your account email and we will send you a reset link.</p>
                                <div class="form-container">
                                    <form id="forgot-password-form" class="form-horizontal"
                                        action="{{ route('web.password.email') }}" method="POST">
                                        @csrf
                                        <div class="form-group mb-3 ajax-msg"></div>
                                        <div class="form-group mb-3 ajax-field">
                                            <input type="email" class="form-control" id="email" placeholder="Email"
                                                name="email" value="{{ old('email') }}" autocomplete="email">
                                            <span class="ajax-error text-danger small"></span>
                                        </div>
                                        <button type="submit" class="btn-main w-100 submit-button">Send reset link</button>
                                        <p class="text-center mt-3 mb-0">
                                            <a href="{{ route('web.login') }}">Back to login</a>
                                        </p>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <div class="login_main">
                                <img src="{{ url('public/web_theme/assets/images/login.png') }}" alt=""
                                    class="img-fluid">
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
        function clearForgotErrors() {
            $('#forgot-password-form .ajax-error').text('');
            $('#forgot-password-form .ajax-msg').html('');
        }

        function handleForgotResponse(res) {
            if (res.status == 1) {
                $('#forgot-password-form .ajax-msg').html('<div class="alert alert-success">' + (res.msg || 'Success') + '</div>');
                $('#forgot-password-form .submit-button').prop('disabled', false).text('Send reset link');
                return;
            }

            if (res.error_array) {
                Object.keys(res.error_array).forEach(function(key) {
                    const field = $('#forgot-password-form [name="' + key + '"]');
                    if (field.length) {
                        field.closest('.ajax-field').find('.ajax-error').text(res.error_array[key]);
                    }
                });
            }

            if (res.error) {
                $('#forgot-password-form .ajax-msg').html('<div class="alert alert-danger">' + res.error + '</div>');
            }

            $('#forgot-password-form .submit-button').prop('disabled', false).text('Send reset link');
        }

        $(document).on('submit', '#forgot-password-form', function(e) {
            e.preventDefault();
            clearForgotErrors();

            const $form = $(this);
            const $btn = $form.find('.submit-button');
            $btn.prop('disabled', true).text('Please wait...');

            $.post($form.attr('action'), $form.serializeArray(), handleForgotResponse, 'json');
        });
    </script>
@endsection
