@extends('web.layouts.app')
@section('title', 'Reset password | EliteGrade')

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
                                    class="mb-2">New password</h3>
                                <p class="mb-5 text-center">Choose a new password for your account.</p>
                                <div class="form-container">
                                    @if ($errors->any())
                                        <div class="alert alert-danger mb-3">
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <form class="form-horizontal" action="{{ route('web.password.update') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $token }}">
                                        <input type="hidden" name="email" value="{{ $email }}">
                                        <div class="form-group mb-3">
                                            <label class="small text-muted" for="reset-email">Email</label>
                                            <input type="email" class="form-control" id="reset-email" value="{{ $email }}"
                                                disabled autocomplete="email">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small text-muted" for="password">New password</label>
                                            <input type="password" class="form-control" id="password" name="password"
                                                required autocomplete="new-password" minlength="6">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small text-muted" for="password_confirmation">Confirm password</label>
                                            <input type="password" class="form-control" id="password_confirmation"
                                                name="password_confirmation" required autocomplete="new-password" minlength="6">
                                        </div>
                                        <button type="submit" class="btn-main w-100">Update password</button>
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
