@extends('web.layouts.app')

@section('title', 'Welcome to EliteGrade')

@section('css')
<style>
    header.float-start.w-100 {
        position: absolute;
        z-index: 10;
    }
    .about-inner-banner img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        display: block;
    }
    .about-inner-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.25);
    }
    .about-inner-banner .inner_banner_caption {
        z-index: 1;
    }
    @media (max-width: 768px) {
        .about-inner-banner img {
            height: 180px;
        }
    }
</style>
@endsection
@section('content')
<div class="inner_banner_section about-inner-banner position-relative">
	<img src="{{ url('public/web_theme/assets/images/inner-banner.jpg') }}" alt="" class="img-fluid"> 
	<div class="inner_banner_caption">About Us</div>
</div>
	  
	<div class="about_us_section float-start w-100"  style="background-color: #FFF;">
	  <div class="container">
		<h3 class="main-title text-black">About EliteGrade</h3>
		<p class="text-black mb-0">EliteGrade was conceived in 2025 with the objective of simplifying the administrative and
operational demands placed on educators within private tutoring environments.
Across coaching institutes and independent classrooms, academic performance tracking
has long relied on fragmented tools — manual grade entry, spreadsheet-based records,
and disconnected communication when sharing marks with students and parents. These
processes not only increase administrative overhead for teachers, but also introduce
inconsistencies in performance reporting and classroom management.<br><br>
EliteGrade was built to streamline these backend workflows by centralizing grading,
attendance, reporting, communication, and fee tracking within a structured platform. Its
goal is to make academic administration more efficient for educators while maintaining
clarity and consistency across learning environments.<br><br>
Developed in Mumbai by founders Reyaansh, Suhaan, and Yohaan, the platform is
designed with a long-term vision of replacing fragmented academic tools with
standardized systems for performance tracking and institutional management. By
enabling data-backed teaching practices and improving operational clarity, EliteGrade
aims to support a more structured and scalable tutoring ecosystem — both locally and
globally.</p>
	  </div>
	</div>  
	  
	  <div class="about_us_section call-to-action float-start w-100">
	  <div class="container">
		<h3 class="main-title text-black text-center mb-2"
			>Ready to Transform Your Education?</h3>
		<p class="text-black mb-0 text-center">Join EliteGrade today and experience the future of educational management.</p>
		  <a href="{{ url('/contact') }}" class="btn-main header-btn mt-0" style="background-color: #FFF; color: #d8a850; width: 234px;">GET IN TOUCH</a>
	  </div>
	</div>  
@endsection


