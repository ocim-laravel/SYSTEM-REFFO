<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Division;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Policies\CustomerPolicy;
use App\Policies\DivisionPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkCalendarPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Division::class, DivisionPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(WorkCalendar::class, WorkCalendarPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
    }
}
