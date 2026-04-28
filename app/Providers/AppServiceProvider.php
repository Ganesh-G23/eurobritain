<?php

namespace App\Providers;

use App\Models\PortalUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        View::composer('web.user.student.layouts.navigate', function ($view) {
            $layoutStudentNotifications = collect();
            $StudentUnreadNotificationCount = 0;

            $portal = session('portal_user');
            $studentId = (int) ($portal['id'] ?? 0);
            $role = (int) ($portal['role'] ?? 0);

            $conn = DB::getDefaultConnection();
            if ($studentId > 0 && $role === 2 && Schema::connection($conn)->hasTable('notifications')) {
                $user = PortalUser::whereKey($studentId)->where('role', 2)->whereNull('deleted_at')->first();

                if ($user) {
                    $morph = $user->getMorphClass();
                    $layoutStudentNotifications = DB::connection($conn)->table('notifications')
                        ->where('notifiable_id', $studentId)
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($morph) {
                            $q->where('notifiable_type', $morph)
                                ->orWhere('notifiable_type', PortalUser::class);
                        })
                        ->orderByDesc('created_at')
                        ->limit(15)
                        ->get();

                    $StudentUnreadNotificationCount = DB::connection($conn)->table('notifications')
                        ->where('notifiable_id', $studentId)
                        ->whereNull('deleted_at')
                        ->whereNull('read_at')
                        ->where(function ($q) use ($morph) {
                            $q->where('notifiable_type', $morph)
                                ->orWhere('notifiable_type', PortalUser::class);
                        })
                        ->count();
                }
            }

            $view->with([
                'layoutStudentNotifications' => $layoutStudentNotifications,
                'StudentUnreadNotificationCount' => $StudentUnreadNotificationCount,
            ]);
        });

        View::composer('web.user.layouts.navigate', function ($view) {
            $layoutParentNotifications = collect();
            $layoutParentUnreadNotificationCount = 0;

            $portal = session('portal_user');
            $parentId = (int) ($portal['id'] ?? 0);
            $role = (int) ($portal['role'] ?? 0);

            $conn = DB::getDefaultConnection();
            if ($parentId > 0 && $role === 3 && Schema::connection($conn)->hasTable('notifications')) {
                $user = PortalUser::whereKey($parentId)->where('role', 3)->whereNull('deleted_at')->first();

                if ($user) {
                    $morph = $user->getMorphClass();
                    $layoutParentNotifications = DB::connection($conn)->table('notifications')
                        ->where('notifiable_id', $parentId)
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($morph) {
                            $q->where('notifiable_type', $morph)
                                ->orWhere('notifiable_type', PortalUser::class);
                        })
                        ->orderByDesc('created_at')
                        ->limit(15)
                        ->get();

                    $layoutParentUnreadNotificationCount = DB::connection($conn)->table('notifications')
                        ->where('notifiable_id', $parentId)
                        ->whereNull('deleted_at')
                        ->whereNull('read_at')
                        ->where(function ($q) use ($morph) {
                            $q->where('notifiable_type', $morph)
                                ->orWhere('notifiable_type', PortalUser::class);
                        })
                        ->count();
                }
            }

            $view->with([
                'layoutParentNotifications' => $layoutParentNotifications,
                'layoutParentUnreadNotificationCount' => $layoutParentUnreadNotificationCount,
            ]);
        });

        View::composer('web.user.layouts.navigate', function ($view) {

            $layoutTeacherNotifications = collect();
            $teacherUnreadNotificationCount = 0;
        
            $portal = session('portal_user');
            $teacherId = (int) ($portal['id'] ?? 0);
            $role = (int) ($portal['role'] ?? 0);
        
            $conn = DB::getDefaultConnection();
            if ($teacherId > 0 && $role === 1 && Schema::connection($conn)->hasTable('notifications')) {
                $user = PortalUser::whereKey($teacherId)
                    ->where('role', 1)
                    ->whereNull('deleted_at')
                    ->first();

                if ($user) {
                    $morph = $user->getMorphClass();
                    $layoutTeacherNotifications = DB::connection($conn)->table('notifications')
                        ->where('notifiable_id', $teacherId)
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($morph) {
                            $q->where('notifiable_type', $morph)
                                ->orWhere('notifiable_type', PortalUser::class);
                        })
                        ->orderByDesc('created_at')
                        ->limit(15)
                        ->get();

                    $teacherUnreadNotificationCount = DB::connection($conn)->table('notifications')
                        ->where('notifiable_id', $teacherId)
                        ->whereNull('deleted_at')
                        ->whereNull('read_at')
                        ->where(function ($q) use ($morph) {
                            $q->where('notifiable_type', $morph)
                                ->orWhere('notifiable_type', PortalUser::class);
                        })
                        ->count();
                }
            }
        
            $view->with([
                'layoutTeacherNotifications' => $layoutTeacherNotifications,
                'teacherUnreadNotificationCount' => $teacherUnreadNotificationCount,
            ]);
        });
        
    }
}
