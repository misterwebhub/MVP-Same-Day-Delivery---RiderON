<?php

namespace App\Repositories\Contracts;

use App\Models\DeliveryPartner;
use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface extends SoftDeletableRepositoryInterface
{
    /**
     * Verified, active, non-busy delivery partners homed in the order's
     * origin city — mirrors PartnerAssignmentService::attemptAssignment()'s
     * eligibility query but returns every match for manual admin picking.
     *
     * @return Collection<int, DeliveryPartner>
     */
    public function eligiblePartners(Order $order): Collection;
}
