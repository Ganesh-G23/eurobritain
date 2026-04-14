<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PortalPasswordResetController;
use App\Http\Controllers\Web\UserDashboardController;
use App\Http\Controllers\Web\UserTeacherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StudentController;

// Web Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('web.home');
Route::get('/about', [HomeController::class, 'about'])->name('web.about');
Route::get('/services', [HomeController::class, 'services'])->name('web.services');
Route::get('/contact', [HomeController::class, 'contact'])->name('web.contact');
Route::get('/login', [HomeController::class, 'login'])->name('web.login');
Route::post('/login', [CustomerController::class, 'login'])->name('web.login.submit');
Route::get('/sign-up', [HomeController::class, 'register'])->name('web.register');

Route::get('/forgot-password', [PortalPasswordResetController::class, 'showForgotPasswordForm'])->name('web.password.request');
Route::post('/forgot-password', [PortalPasswordResetController::class, 'sendResetLinkEmail'])->name('web.password.email');
Route::get('/reset-password/{token}', [PortalPasswordResetController::class, 'showResetForm'])->name('web.password.reset');
Route::post('/reset-password', [PortalPasswordResetController::class, 'reset'])->name('web.password.update');

// User (Portal) Panel Routes
Route::prefix('user')->group(function () {
    Route::get('/', function () {
        return redirect('user/dashboard');
    });
    Route::get('/dashboard', [UserDashboardController::class, 'index']);
    Route::get('/profile', [UserDashboardController::class, 'profile']);
    Route::get('/profile/settings', [UserDashboardController::class, 'teacherProfileSettings']);
    Route::post('/profile/save_profile', [UserDashboardController::class, 'saveProfile']);
    Route::post('/profile/save_teacher_settings', [UserDashboardController::class, 'saveTeacherSettings']);
    Route::get('/security', [UserDashboardController::class, 'security']);
    Route::post('/security/save_change_password', [UserDashboardController::class, 'saveChangePassword']);
    Route::post('/update_password_popup', [UserDashboardController::class, 'updatePasswordPopup']);
    Route::post('/skip_password_popup', [UserDashboardController::class, 'skipPasswordPopup']);
    Route::get('/logout', [UserDashboardController::class, 'logout']);
    Route::post('/notification/delete', [UserDashboardController::class, 'delete'])->name('notification.delete');

    // Student: Select teacher (header/footer-less view) and access teacher panel
    Route::get('/select-teacher', [UserDashboardController::class, 'selectTeacher']);
    Route::post('/select-teacher/access', [UserDashboardController::class, 'accessTeacher']); // legacy
    Route::get('/select-teacher/access/{teacherId}', [UserDashboardController::class, 'accessTeacher']); // legacy
    // Student module pages under selected teacher context
    Route::get('/student/dashboard', [UserDashboardController::class, 'studentDashboard']);
    Route::get('/student/classrooms', [UserDashboardController::class, 'studentClassrooms']);
    Route::get('/student/classroom/{id}', [UserDashboardController::class, 'studentClassroomShow']);
    Route::get('/student/attendance', [UserDashboardController::class, 'studentAttendance']);
    Route::get('/student/report', [UserDashboardController::class, 'studentReport']);

    // Parent: Select student (header/footer-less view) and access student portal
    Route::get('/select-student', [UserDashboardController::class, 'selectStudent']);
    Route::post('/select-student/access', [UserDashboardController::class, 'accessStudent']);
    Route::get('/select-student/access/{studentId}', [UserDashboardController::class, 'accessStudent']);
    Route::get('/parent/dashboard', [UserDashboardController::class, 'parentDashboard']);
    Route::get('/parent/classrooms', [UserDashboardController::class, 'parentClassrooms']);
    Route::get('/parent/classroom/{id}', [UserDashboardController::class, 'parentClassroomShow']);

    // User-side Teacher management (logged-in teacher) - original simple routes
    Route::prefix('teacher')->group(function () {
        Route::get('/', [UserTeacherController::class, 'index']);
        Route::get('/classrooms', [UserTeacherController::class, 'classrooms']);
        Route::get('/batches', [UserTeacherController::class, 'batches']);
        Route::get('/students', [UserTeacherController::class, 'students']);
        Route::get('/students/view/{id}', [UserTeacherController::class, 'viewStudent']);
        // CRUD
        Route::post('/classrooms/save', [UserTeacherController::class, 'saveClassroom']);
        Route::post('/classrooms/delete', [UserTeacherController::class, 'deleteClassroom']);
        Route::post('/batches/save', [UserTeacherController::class, 'saveBatch']);
        Route::post('/batches/delete', [UserTeacherController::class, 'deleteBatch']);
        Route::post('/students/save', [UserTeacherController::class, 'saveStudent']);
        Route::post('/students/sync-enrollments', [UserTeacherController::class, 'syncStudentEnrollments']);
        Route::get('/batches-by-classroom', [UserTeacherController::class, 'getBatchesByClassroom']);
        Route::post('/students/delete', [UserTeacherController::class, 'deleteStudent']);
        Route::get('/students/bulk-sample', [UserTeacherController::class, 'downloadStudentBulkSample']);
        Route::post('/students/bulk-upload', [UserTeacherController::class, 'bulkUploadStudents']);
        Route::match(['get', 'post'], '/classrooms/details/{id}/export', [UserTeacherController::class, 'exportClassroomMarks']);
        Route::post('/classrooms/details/{id}/import-marks', [UserTeacherController::class, 'importMarksFromCsv']);
        Route::get('/classrooms/details/{id}', [UserTeacherController::class, 'classroomsDetails']);
        Route::post('/exams/save', [UserTeacherController::class, 'saveExam']);
        Route::post('/exams/marks/save', [UserTeacherController::class, 'saveExamMarksColumn']);
    });
});

Route::middleware('prevent-back')
    ->prefix('admin')
    ->group(function () {
        Route::middleware('admin-auth')->group(function () {
            Route::get('login', [AuthController::class, 'login'])->name('login');
            Route::post('verify_login', [AuthController::class, 'verifyLogin']);
        });

        Route::middleware('admin-all')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('profile', [ProfileController::class, 'index']);
            Route::post('profile/save_profile', [ProfileController::class, 'save_profile']);
            Route::get('security', [ProfileController::class, 'security']);
            Route::post('security/save_change_password', [ProfileController::class, 'save_change_password']);
            Route::get('logout', [DashboardController::class, 'logout']);

            Route::prefix('common')->group(function () {
                Route::post('upload_ppt', [CommonController::class, 'upload_ppt']);
                Route::post('upload_files', [CommonController::class, 'upload_files']);
                Route::post('upload_ckeditor_image', [CommonController::class, 'upload_ckeditor_image']);
            });

            // teacher
            Route::get('teacher', [TeacherController::class, 'list']);
            Route::get('teacher/form', [TeacherController::class, 'form']);
            Route::get('teacher/form/{id}', [TeacherController::class, 'form']);
            Route::post('teacher/save', [TeacherController::class, 'save']);
            Route::get('teacher/view', [TeacherController::class, 'view']);
            Route::post('teacher/delete', [TeacherController::class, 'delete']);
            Route::post('teacher/save_classroom', [TeacherController::class, 'saveClassroom']);
            Route::post('teacher/delete_classroom', [TeacherController::class, 'deleteClassroom']);
            Route::post('teacher/save_batch', [TeacherController::class, 'saveBatch']);
            Route::post('teacher/delete_batch', [TeacherController::class, 'deleteBatch']);
            Route::post('teacher/save_student', [TeacherController::class, 'saveStudent']);
            Route::post('teacher/sync_student_enrollments', [TeacherController::class, 'syncStudentEnrollments']);
            Route::post('teacher/delete_student', [TeacherController::class, 'deleteStudent']);
            Route::get('teacher/student_view', [TeacherController::class, 'viewStudent']);
            Route::get('teacher/get_batches_by_classroom', [TeacherController::class, 'getBatchesByClassroom']);
            Route::get('teacher/login_as/{id}', [TeacherController::class, 'loginAs']);

            // student
            Route::get('student', [StudentController::class, 'list']);
            Route::get('student/add', [StudentController::class, 'add']);
            Route::get('student/edit', [StudentController::class, 'edit']);
            Route::get('student/enrollment-options', [StudentController::class, 'enrollmentOptions']);
            Route::post('student/delete', [StudentController::class, 'deleteStudent']);
            Route::get('student/form', [StudentController::class, 'form']);
            Route::post('student/save', [StudentController::class, 'saveStudent']);
            Route::get('student/view', [StudentController::class, 'view']);

            // Admin bulk student upload/sample
            Route::get('teacher/students/bulk-sample', [TeacherController::class, 'downloadStudentBulkSample']);
            Route::post('teacher/students/bulk-upload', [TeacherController::class, 'bulkUploadStudents']);
        });

        Route::prefix('common')->group(function () {
            Route::post('upload_files', [CommonController::class, 'upload_files']);
            Route::post('upload_ckeditor_image', [CommonController::class, 'uploadCkeditorImage'])->name('upload.ckeditor.image');
        });
    });
