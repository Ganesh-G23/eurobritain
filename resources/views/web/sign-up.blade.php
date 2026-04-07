@extends('web.layouts.app')
@section('title', 'Sign Up | EliteGrade')
@section('content')
<div class="about_us_section_new float-start w-100">
  <div class="container">
    <div class="contact-us-section">
      <div class="row">
        <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12" style="float: none; margin: 0 auto;">
          <div class="enquay-form-section bg-white">
            <h3 style="color: #000; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 1px;" class="mb-2">Sign Up</h3>
			  <p class="mb-5 text-center">Sign up to Continue</p>
            <form class="form-horizontal" action="">
              <div class="form-group mb-3">
                <input type="text" class="form-control" id="email" placeholder="Name" name="">
              </div>
              <div class="form-group mb-3">
                <input type="text" class="form-control" id="contact-no" placeholder="Email" name="Email">
              </div>
              <div class="form-group mb-3">
                <input type="text" class="form-control" id="contacxt-no" placeholder="Phone" name="Phone">
              </div>
              <div class="form-group mb-3">
                <input type="text" class="form-control" id="contact-dno" placeholder="Password" name="Phone">
              </div>
              <div class="form-group">
                <button type="submit" class="btn-main w-100">Create Account</button>
              </div>
              <div class="form-group mb-3 float-start w-100 mt-3 mb-0">
                <p class="text-center mb-0">Already have an account ? <a href="{{ url('/login') }}" class="register_btn">Sign In</a></p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
