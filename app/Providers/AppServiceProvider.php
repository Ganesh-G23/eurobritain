<?php

namespace App\Providers;

use App\Models\PortalUser;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

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
        View::composer('web.user.student.layouts.navigate', function ($view) {
            $layoutStudentNotifications = collect();
            $layoutStudentUnreadNotificationCount = 0;

            $portal = session('portal_user');
            $studentId = (int) ($portal['id'] ?? 0);
            $role = (int) ($portal['role'] ?? 0);

            if ($studentId > 0 && $role === 2 && Schema::hasTable('notifications')) {
                $user = PortalUser::whereKey($studentId)->where('role', 2)->whereNull('deleted_at')->first();

                if ($user) {
                    $layoutStudentNotifications = DB::table('notifications')->where('notifiable_id', $studentId)->whereNull('deleted_at')->where('notifiable_type', $user->getMorphClass())->orderByDesc('created_at')->limit(15)->get();

                    $layoutStudentUnreadNotificationCount = DB::table('notifications')->where('notifiable_id', $studentId)->where('notifiable_type', $user->getMorphClass())->whereNull('deleted_at')->count();
                }
            }

            $view->with([
                'layoutStudentNotifications' => $layoutStudentNotifications,
                'layoutStudentUnreadNotificationCount' => $layoutStudentUnreadNotificationCount,
            ]);
        });

        View::composer('web.user.layouts.navigate', function ($view) {
            $layoutParentNotifications = collect();
            $layoutParentUnreadNotificationCount = 0;

            $portal = session('portal_user');
            $parentId = (int) ($portal['id'] ?? 0);
            $role = (int) ($portal['role'] ?? 0);

            if ($parentId > 0 && $role === 3 && Schema::hasTable('notifications')) {
                $user = PortalUser::whereKey($parentId)->where('role', 3)->whereNull('deleted_at')->first();

                if ($user) {
                    $layoutParentNotifications = DB::table('notifications')->where('notifiable_id', $parentId)->whereNull('deleted_at')->where('notifiable_type', $user->getMorphClass())->orderByDesc('created_at')->limit(15)->get();

                    $layoutParentUnreadNotificationCount = DB::table('notifications')->where('notifiable_id', $parentId)->where('notifiable_type', $user->getMorphClass())->whereNull('deleted_at')->count();
                }
            }

            $view->with([
                'layoutParentNotifications' => $layoutParentNotifications,
                'layoutParentUnreadNotificationCount' => $layoutParentUnreadNotificationCount,
            ]);
        });
    }
}
