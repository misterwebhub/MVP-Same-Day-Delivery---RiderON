<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\QuoteRequest;
use App\Services\PricingEngine;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    public function __construct(private readonly PricingEngine $pricingEngine)
    {
    }

    public function quote(QuoteRequest $request): JsonResponse
    {
        $quote = $this->pricingEngine->quote($request->validated());

        return $this->success($quote->toArray());
    }
}
