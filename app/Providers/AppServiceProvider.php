<?php

namespace App\Providers;

use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryProof;
use App\Models\DesignFile;
use App\Models\DesignJob;
use App\Models\Division;
use App\Models\FinishingJob;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Packing;
use App\Models\PrintJob;
use App\Models\ReprintRequest;
use App\Models\Rework;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\DesignPolicy;
use App\Policies\DivisionPolicy;
use App\Policies\FinishingPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductionPolicy;
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
        Gate::policy(Delivery::class, DeliveryPolicy::class);
        Gate::policy(DeliveryProof::class, DeliveryPolicy::class);
        Gate::policy(Division::class, DivisionPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(OrderItem::class, DesignPolicy::class);
        Gate::policy(DesignJob::class, DesignPolicy::class);
        Gate::policy(DesignFile::class, DesignPolicy::class);
        Gate::policy(ChangeRequest::class, DesignPolicy::class);
        Gate::policy(PrintJob::class, ProductionPolicy::class);
        Gate::policy(ReprintRequest::class, ProductionPolicy::class);
        Gate::policy(FinishingJob::class, FinishingPolicy::class);
        Gate::policy(Rework::class, FinishingPolicy::class);
        Gate::policy(Packing::class, FinishingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(WorkCalendar::class, WorkCalendarPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
    }
}
