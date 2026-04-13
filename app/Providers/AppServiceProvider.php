<?php

namespace App\Providers;

use App\Models\PortalUser;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
                $user = PortalUser::query()->whereKey($studentId)->where('role', 2)->whereNull('deleted_at')->first();
                if ($user) {
                    $listQuery = $user->notifications()->latest()->limit(15);
                    if (Schema::hasColumn('notifications', 'deleted_at')) {
                        $listQuery->whereNull('notifications.deleted_at');
                    }
                    $layoutStudentNotifications = $listQuery->get();

                    $unreadQuery = $user->unreadNotifications();
                    if (Schema::hasColumn('notifications', 'deleted_at')) {
                        $unreadQuery->whereNull('notifications.deleted_at');
                    }
                    $layoutStudentUnreadNotificationCount = (int) $unreadQuery->count();
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
                $user = PortalUser::query()->whereKey($parentId)->where('role', 3)->whereNull('deleted_at')->first();
                if ($user) {
                    $studentIds = PortalUser::query()
                        ->where('role', 2)
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($parentId) {
                            $q->where('parent_id', $parentId);
                            if (Schema::hasTable('parent_student_map')) {
                                $q->orWhereExists(function ($sub) use ($parentId) {
                                    $sub->selectRaw('1')
                                        ->from('parent_student_map')
                                        ->whereColumn('parent_student_map.student_id', 'portal_user.id')
                                        ->where('parent_student_map.parent_id', $parentId);
                                });
                            }
                        })
                        ->pluck('id');

                    if ($studentIds->isNotEmpty()) {
                        $morph = $user->getMorphClass();
                        $listQuery = DatabaseNotification::query()
                            ->where('notifiable_type', $morph)
                            ->whereIn('notifiable_id', $studentIds)
                            ->latest()
                            ->limit(15);
                        if (Schema::hasColumn('notifications', 'deleted_at')) {
                            $listQuery->whereNull('notifications.deleted_at');
                        }
                        $layoutParentNotifications = $listQuery->get();

                        $unreadQuery = DatabaseNotification::query()
                            ->where('notifiable_type', $morph)
                            ->whereIn('notifiable_id', $studentIds)
                            ->whereNull('read_at');
                        if (Schema::hasColumn('notifications', 'deleted_at')) {
                            $unreadQuery->whereNull('notifications.deleted_at');
                        }
                        $layoutParentUnreadNotificationCount = (int) $unreadQuery->count();
                    }
                }
            }

            $view->with([
                'layoutParentNotifications' => $layoutParentNotifications,
                'layoutParentUnreadNotificationCount' => $layoutParentUnreadNotificationCount,
            ]);
        });
    }
}
