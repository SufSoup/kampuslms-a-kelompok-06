<?php

namespace App\Providers;

use App\Events\AssignmentPublished;
use App\Events\GradeGiven;
use App\Listeners\SendAssignmentNotification;
use App\Listeners\SendGradeNotification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        Paginator::defaultView('pagination::default');

        Event::listen(AssignmentPublished::class, SendAssignmentNotification::class);
        Event::listen(GradeGiven::class, SendGradeNotification::class);
    }
}
