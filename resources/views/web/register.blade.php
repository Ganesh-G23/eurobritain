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
  <div class="container">
    <div class="contact-us-section"> <a href="{{ url('/') }}" class="back_btn">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4">
        <path d="m12 19-7-7 7-7"></path>
        <path d="M19 12H5"></path>
      </svg>
      Back to Home</a>
      <div class="row">
        <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12" style="float: none; margin: 0 auto;">
          <div class="enquay-form-section bg-white">
            <h3 style="color: #000; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 1px;" class="mb-2">Create Account </h3>
            <p class="mb-5 text-center">Sign in to your account</p>
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
