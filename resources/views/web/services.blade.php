@extends('web.layouts.app')

@section('title', 'Welcome to EliteGrade')

@section('css')
<style>
    header.float-start.w-100 {
        position: absolute;
        z-index: 10;
    }
    .services-inner-banner img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        display: block;
    }
    .services-inner-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.25);
    }
    .services-inner-banner .inner_banner_caption {
        z-index: 1;
    }
    @media (max-width: 768px) {
        .services-inner-banner img {
            height: 180px;
        }
    }
</style>
@endsection

@section('content')
<div class="inner_banner_section services-inner-banner position-relative">
	<img src="{{ url('public/web_theme/assets/images/inner-banner.jpg') }}" alt="" class="img-fluid"> 
	<div class="inner_banner_caption">Services</div>
</div>
	  
	<div class="feature_section_main float-start w-100">
	  	<div class="container">
		  <h3 class="main-title text-black text-center mb-5">Teacher Control</h3> 
			
		  <div class="feature_iunner_section float-start w-100">
			<div class="row">
			  	<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Multi-Classroom Management</h3>
						<p class="text-muted-foreground">Create and organize multiple classrooms with custom batch structuring from a
unified dashboard.</p>
					</div>
				</div>	
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Real-Time Gradebooks</h3>
						<p class="text-muted-foreground">Maintain spreadsheet-style marks tables with autosave and exam-wise
performance tracking.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Attendance Ledger</h3>
						<p class="text-muted-foreground">Track attendance across batches with Present, Absent, and Leave status
<br>updates.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Assignment &amp; Announcements</h3>
						<p class="text-muted-foreground">Post tasks or updates to selected classrooms or batches with file attachments.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Leave Request Approval</h3>
						<p class="text-muted-foreground">Review and approve student leave requests with automatic attendance updates.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Performance Analytics</h3>
						<p class="text-muted-foreground">Monitor individual student progress through exam history and visual graphs.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Academic Calendar</h3>
						<p class="text-muted-foreground">Schedule tests, holidays, and tasks using a color-coded classroom calendar.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Fee Tracking</h3>
						<p class="text-muted-foreground">Log monthly payments manually or through integrated payment gateway support.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Leaderboards</h3>
						<p class="text-muted-foreground">Rank students batch-wise or exam-wise based on overall academic
performance.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Messaging</h3>
						<p class="text-muted-foreground">Communicate through direct or group chats with students and parents.</p>
					</div>
				</div>
			</div>
		  </div>
			
			
		
			
			
		</div>
	  </div> 
	  
	  
	  

	  
	  <div class="about_us_section call-to-action float-start w-100">
	
	  	<div class="container">
			<h3 class="main-title text-black text-center mb-5 mt-0">Student Control</h3> 
			
		  <div class="feature_iunner_section float-start w-100">
			<div class="row">
			  	<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Study Dashboard</h3>
						<p class="text-muted-foreground">View grades, attendance, deadlines, and announcements a personalized
homepage.</p>
					</div>
				</div>	
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Assignment Submissions</h3>
						<p class="text-muted-foreground">Upload and submit assignments directly when enabled by the teacher.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Progress Tracking</h3>
						<p class="text-muted-foreground">Access exam history and performance analytics across classrooms.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Leave Applications</h3>
						<p class="text-muted-foreground">Submit leave requests with date selection and approval tracking.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Event Reminders</h3>
						<p class="text-muted-foreground">Receive task and exam reminders directly via notifications.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Privacy Controls</h3>
						<p class="text-muted-foreground">Opt out of leaderboard rankings while retaining performance visibility.</p>
					</div>
				</div>
			</div>
		  </div>
	  </div>
	</div>  
	  
	  
	  
	  <div class="feature_section_main float-start w-100">
	  	<div class="container">
		  <h3 class="main-title text-black text-center mb-5">Parent View</h3> 
			
		  <div class="feature_iunner_section float-start w-100">
			<div class="row">
			  	<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">
						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Academic Dashboard</h3>
						<p class="text-muted-foreground">View linked student grades, attendance, and performance trends.</p>
					</div>
				</div>	
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Monthly Reports</h3>
						<p class="text-muted-foreground">Receive automated progress summaries via email.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Fee Visibility</h3>
						<p class="text-muted-foreground">Track payment history and status updates.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Direct Messaging</h3>
						<p class="text-muted-foreground">Communicate privately with teachers when required.</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-3">
					<div class="feature-card">

						<h3 class="text-black text-xl font-display font-bold text-foreground mb-3 feature-title">Parent Groups</h3>
						<p class="text-muted-foreground">Participate in classroom-based communication channels.</p>
					</div>
				</div>
				
				
			</div>
		  </div>
			
			
		
			
			
		</div>
	  </div> 
	  
	  
@endsection