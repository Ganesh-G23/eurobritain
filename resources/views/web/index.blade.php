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
  <div class="top_banner_section">
	  <div class="container">
		  <div class="row">
			<div class="col-lg-6 col-md-12 col-sm-12 col-12">
			  <div class="top_banner_left_section float-start w-100 d-flex justify-content-center  h-100" style="flex-direction: column;">
				 <h1 class="text-color">Put your classroom on <span class="golden-color">Autopilot</span></h1>	
				 <p class="text-color mt-2">EliteGrade is a website that efficiently allows organization for tuition teachers who are usually
swamped with data regarding students.</p>
				  <a href="{{ url('/login') }}" class="btn-main">Sign In</a>
			  </div>
			</div>
			<div class="col-lg-6 col-md-12 col-sm-12 col-12">
			  <div class="top_banner_right_section float-start w-100">
				  <img src="{{ url('public/web_theme/assets/images/main-banner.png') }}"  alt="" class="img-fluid">
			  </div>
			</div>  
		  </div>
	  </div>  
  </div>
	  
	<div class="about_us_section float-start w-100">
	  <div class="container">
		<h3 class="main-title text-black">About EliteGrade</h3>
		<p class="text-black mb-0">EliteGrade is a role-based classroom operating system for private tutors and
academic teams to manage grades, attendance, assignments, fees, reports, and
parent communication within a single platform.<br><br>
Teachers can track batch-wise performance through real-time gradebooks and
visual analytics, manage submissions and attendance, and generate academic
reports across classrooms. Students access personalized dashboards with
assignment timelines, progress tracking, and attendance history, while parents
receive linked read-only access, automated monthly reports, fee status, and
direct communication channels.<br><br>
The platform standardizes academic tracking and reduces manual administrative
processes across teaching environments.</p>
	  </div>
	</div>  
	  
	  
	  
	 <div class="our_services_section float-start w-100">
	    <div class="container">
			<h3 class="main-title text-black text-center">Our Services</h3> 
			<div class="services_inner_section float-start w-100">
				<div class="row">
					<div class="col-lg-4 col-md-6 col-sm-12 col-12">
						<div class="service_section_box float-start w-100">
							<img width="50" height="50" src="https://img.icons8.com/ios/50/teacher.png" alt="teacher" class="m-auto text-center d-block mb-2"/>
							<h4 class="text-black" style="color: rgb(205 152 61) !important;">Teacher Control</h4>
							<p class="text-center">A powerful dashboard that lets teachers manage grades, attendance, assignments, and reports in one place while tracking student performance in real time.</p>
						</div>
					</div>	
					<div class="col-lg-4 col-md-6 col-sm-12 col-12">
						<div class="service_section_box margin_extra float-start w-100">
							<img width="50" height="50" src="https://img.icons8.com/dotty/80/student-female.png" alt="student-female" class="m-auto text-center d-block mb-2"/>
							<h4 class="text-black" style="color: rgb(205 152 61) !important;">Student Control</h4>
							<p class="text-center">Students access a personalized dashboard to view assignments, track academic progress, check attendance history, and stay updated with upcoming deadlines.</p>
						</div>
					</div>
					<div class="col-lg-4 col-md-12 col-sm-12 col-12">
						<div class="service_section_box margin_extra float-start w-100">
							<img width="50" height="50" src="https://img.icons8.com/ios/50/parent-guardian.png" alt="parent-guardian" class="m-auto text-center d-block mb-2"/>
							<h4 class="text-black" style="color: rgb(205 152 61) !important;">Parent View</h4>
							<p class="text-center">Parents receive secure read-only access to monitor student performance, attendance records, assignment updates, and monthly academic reports.</p>
							
						</div>
					</div>
				</div>
			</div>
		</div>
	 </div> 
	  
	  
	  <div class="feature_section_main float-start w-100">
	  	<div class="container">
		  <h3 class="main-title text-black text-center mb-5">Powerful Features</h3> 
			
		  <div class="feature_iunner_section float-start w-100">
			<div class="row">
			  	<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card">
						<div class="mb-3 w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield w-7 h-7 text-primary"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
							</svg>
						</div>
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Enterprise Security</h3>
						<p class="text-muted-foreground">Multi-role authentication with recovery emails and admin-controlled access. Your data stays protected.</p>
					</div>
				</div>	
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card margin_extra">
						<div class="mb-3 w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-7 h-7 text-primary"><path d="M12 7v14"></path><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path></svg>
						</div>
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Smart Gradebook</h3>
						<p class="text-muted-foreground">Real-time grade tracking with auto-save functionality. Teachers can manage multiple batches effortlessly.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card margin_extra">
						<div class="mb-3 w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-7 h-7 text-primary"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
						</div>
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Classroom Management</h3>
						<p class="text-muted-foreground">Create classrooms with unique codes. Organize students into batches and manage join requests seamlessly.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card mt-3">
						<div class="mb-3 w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-column w-7 h-7 text-primary"><path d="M3 3v16a2 2 0 0 0 2 2h16"></path><path d="M18 17V9"></path><path d="M13 17V5"></path><path d="M8 17v-3"></path></svg>
						</div>
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Progress Analytics</h3>
						<p class="text-muted-foreground">Visual dashboards showing student performance, batch leaderboards, and overall class statistics.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card mt-3">
						<div class="mb-3 w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap w-7 h-7 text-primary"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg>
						</div>
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Instant Updates</h3>
						<p class="text-muted-foreground">Announcements and assignments sync instantly. Students never miss important updates from teachers.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card mt-3">
						<div class="mb-3 w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6 group-hover:bg-primary/20 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-7 h-7 text-primary"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
						</div>
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Controlled Access</h3>
						<p class="text-muted-foreground">Students must be approved before accessing classroom data. Teachers maintain full control.</p>
					</div>
				</div>
			</div>
		  </div>
		</div>
	  </div>
	  
	  <div class="about_us_section call-to-action float-start w-100">
	  <div class="container">
		<h3 class="main-title text-black text-center mb-2"
			>Ready to Transform Your Education?</h3>
		<p class="text-black mb-0 text-center">Join EliteGrade today and experience the future of educational management.</p>
		 <a href="{{ url('/contact') }}" class="btn-main header-btn mt-0" style="background-color: #FFF; color: #d8a850; width: 180px;">Get in Touch</a>
	  </div>
	</div>  
@endsection
