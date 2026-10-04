<?php

namespace App\Providers;

use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\DeliveryPartnerRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\OtpVerificationLogRepositoryInterface;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;
use App\Repositories\Contracts\RefundRepositoryInterface;
use App\Repositories\Contracts\RouteRepositoryInterface;
use App\Repositories\Contracts\RouteScheduleRepositoryInterface;
use App\Repositories\Contracts\StationRepositoryInterface;
use App\Repositories\Contracts\SupportTicketMessageRepositoryInterface;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use App\Repositories\Eloquent\CityRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\DeliveryPartnerRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\OtpVerificationLogRepository;
use App\Repositories\Eloquent\PricingRuleRepository;
use App\Repositories\Eloquent\RefundRepository;
use App\Repositories\Eloquent\RouteRepository;
use App\Repositories\Eloquent\RouteScheduleRepository;
use App\Repositories\Eloquent\StationRepository;
use App\Repositories\Eloquent\SupportTicketMessageRepository;
use App\Repositories\Eloquent\SupportTicketRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds every admin-panel repository interface to its Eloquent
 * implementation. Controllers depend on the *Interface contracts only,
 * never on Eloquent\*Repository directly.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
        $this->app->bind(StationRepositoryInterface::class, StationRepository::class);
        $this->app->bind(RouteRepositoryInterface::class, RouteRepository::class);
        $this->app->bind(RouteScheduleRepositoryInterface::class, RouteScheduleRepository::class);
        $this->app->bind(PricingRuleRepositoryInterface::class, PricingRuleRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(DeliveryPartnerRepositoryInterface::class, DeliveryPartnerRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(RefundRepositoryInterface::class, RefundRepository::class);
        $this->app->bind(SupportTicketRepositoryInterface::class, SupportTicketRepository::class);
        $this->app->bind(SupportTicketMessageRepositoryInterface::class, SupportTicketMessageRepository::class);
        $this->app->bind(OtpVerificationLogRepositoryInterface::class, OtpVerificationLogRepository::class);
    }
}
