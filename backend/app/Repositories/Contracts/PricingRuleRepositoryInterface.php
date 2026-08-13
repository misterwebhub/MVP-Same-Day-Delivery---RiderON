<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface PricingRuleRepositoryInterface extends RepositoryInterface
{
    public function forRoute(int $routeId): Collection;
}
