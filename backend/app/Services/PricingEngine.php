<?php

namespace App\Services;

use App\Exceptions\InvalidQuoteTokenException;
use App\Models\Coupon;
use App\Models\PricingRule;
use App\Models\Route;
use App\Models\RouteSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

class PricingEngine
{
    /**
     * @param  array{
     *     route_id: int,
     *     route_schedule_id: int,
     *     weight_slab: string,
     *     quantity: int,
     *     declared_value_paise: int,
     *     coupon_code: ?string,
     * }  $input
     */
    public function quote(array $input): PricingQuote
    {
        $route = Route::query()->findOrFail($input['route_id']);

        RouteSchedule::query()
            ->where('id', $input['route_schedule_id'])
            ->where('route_id', $route->id)
            ->firstOrFail();

        $weightSlab = $input['weight_slab'];
        $weightGrams = config("pricing.weight_slab_grams.$weightSlab");

        if ($weightGrams === null) {
            throw new InvalidArgumentException("Unknown weight slab: {$weightSlab}");
        }

        $quantity = max(1, (int) $input['quantity']);
        $now = Carbon::now();

        $activeRules = $this->loadActiveRules($route->id, $now);

        $breakdown = [];

        $baseRule = $activeRules->get(PricingRule::TYPE_BASE)?->first();
        $baseAmount = $baseRule ? $baseRule->amount_paise * $quantity : 0;
        if ($baseRule) {
            $breakdown[] = ['label' => 'Base Fare', 'amount_paise' => $baseAmount];
        }

        $weightRule = $activeRules->get(PricingRule::TYPE_WEIGHT_SLAB)
            ?->first(fn (PricingRule $rule) => $weightGrams >= ($rule->min_weight_grams ?? 0)
                && $weightGrams <= ($rule->max_weight_grams ?? PHP_INT_MAX));
        $weightAmount = $weightRule ? $weightRule->amount_paise * $quantity : 0;
        if ($weightRule) {
            $breakdown[] = ['label' => 'Weight Charge', 'amount_paise' => $weightAmount];
        }

        $peakRule = $activeRules->get(PricingRule::TYPE_PEAK_HOUR)?->first();
        $peakAmount = 0;
        if ($peakRule) {
            $peakAmount = $peakRule->amount_paise !== null
                ? $peakRule->amount_paise * $quantity
                : (int) round(($baseAmount + $weightAmount) * ((float) $peakRule->percentage / 100));
            $breakdown[] = ['label' => 'Peak Hour Surcharge', 'amount_paise' => $peakAmount];
        }

        $subtotal = $baseAmount + $weightAmount + $peakAmount;

        $discount = 0;
        if (! empty($input['coupon_code'])) {
            $discount = $this->resolveCouponDiscount($input['coupon_code'], $subtotal, $now);
            if ($discount > 0) {
                $breakdown[] = ['label' => 'Coupon Discount', 'amount_paise' => -$discount];
            }
        }

        $discountedSubtotal = $subtotal - $discount;

        $platformFeeRule = $activeRules->get(PricingRule::TYPE_PLATFORM_FEE)?->first();
        $platformFee = 0;
        if ($platformFeeRule) {
            $platformFee = $platformFeeRule->amount_paise !== null
                ? $platformFeeRule->amount_paise
                : (int) round($discountedSubtotal * ((float) $platformFeeRule->percentage / 100));
            $breakdown[] = ['label' => 'Platform Fee', 'amount_paise' => $platformFee];
        }

        $taxable = $discountedSubtotal + $platformFee;

        $taxRule = $activeRules->get(PricingRule::TYPE_TAX)?->first();
        $tax = 0;
        if ($taxRule) {
            $tax = $taxRule->amount_paise !== null
                ? $taxRule->amount_paise
                : (int) round($taxable * ((float) $taxRule->percentage / 100));
            $breakdown[] = ['label' => 'Tax', 'amount_paise' => $tax];
        }

        $total = $taxable + $tax;

        $expiresAt = $now->clone()->addMinutes((int) config('pricing.quote_token_ttl_minutes'));

        $tokenPayload = [
            'route_id' => $route->id,
            'route_schedule_id' => (int) $input['route_schedule_id'],
            'weight_slab' => $weightSlab,
            'quantity' => $quantity,
            'declared_value_paise' => (int) $input['declared_value_paise'],
            'coupon_code' => $input['coupon_code'] ?? null,
            'breakdown' => $breakdown,
            'total_amount_paise' => $total,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        $quoteToken = Crypt::encryptString(json_encode($tokenPayload, JSON_THROW_ON_ERROR));

        return new PricingQuote(
            breakdown: $breakdown,
            totalAmountPaise: $total,
            quoteToken: $quoteToken,
            quoteExpiresAt: $expiresAt,
        );
    }

    /**
     * Non-binding "starting from" price for route-browsing UI: base fare plus
     * the cheapest weight-slab rule, with no coupon/platform-fee/tax applied.
     */
    public function priceFrom(Route $route): int
    {
        $activeRules = $this->loadActiveRules($route->id, Carbon::now());

        $baseAmount = $activeRules->get(PricingRule::TYPE_BASE)?->first()?->amount_paise ?? 0;
        $cheapestWeightAmount = $activeRules->get(PricingRule::TYPE_WEIGHT_SLAB)?->min('amount_paise') ?? 0;

        return $baseAmount + (int) $cheapestWeightAmount;
    }

    /**
     * Decrypts and validates a quote token, returning its trusted payload.
     * The order-creation flow must use these values (never client-sent amounts).
     */
    public function verifyQuoteToken(string $token): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new InvalidQuoteTokenException();
        }

        if (Carbon::parse($payload['expires_at'])->isPast()) {
            throw new InvalidQuoteTokenException('Quote token has expired.');
        }

        return $payload;
    }

    private function loadActiveRules(int $routeId, Carbon $now)
    {
        return PricingRule::query()
            ->where(function ($q) use ($routeId) {
                $q->where('route_id', $routeId)->orWhereNull('route_id');
            })
            ->where('is_active', true)
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->get()
            ->sortByDesc(fn (PricingRule $rule) => $rule->route_id !== null)
            ->groupBy('rule_type');
    }

    private function resolveCouponDiscount(string $code, int $subtotal, Carbon $now): int
    {
        $coupon = Coupon::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now)
            ->first();

        if (! $coupon || $subtotal < ($coupon->min_order_amount_paise ?? 0)) {
            return 0;
        }

        // Note: coupons.usage_limit_total / usage_limit_per_user cannot be
        // enforced yet — there is no coupon-redemption tracking table in the
        // current schema, so only self-contained validity fields are applied.
        $discount = $coupon->discount_type === Coupon::TYPE_PERCENTAGE
            ? (int) round($subtotal * ((float) $coupon->value / 100))
            : (int) round(((float) $coupon->value) * 100);

        if ($coupon->max_discount_paise !== null) {
            $discount = min($discount, $coupon->max_discount_paise);
        }

        return min($discount, $subtotal);
    }
}
