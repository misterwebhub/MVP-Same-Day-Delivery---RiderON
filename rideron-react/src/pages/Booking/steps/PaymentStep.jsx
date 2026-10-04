import React, { useEffect, useState } from "react";
import { ApiClientError } from "@rideron/api-client";
import { apiClient } from "../../../lib/apiClient";
import { openRazorpayCheckoutWeb } from "../../../utils/razorpayWeb";
import { formatPaise } from "../../../utils/currency";
import { useAuth } from "../../../context/AuthContext";

/**
 * Checkout step — branches on order.payment.provider (set server-side from
 * PAYMENT_DRIVER). `razorpay`: opens the real Razorpay Checkout (hosted
 * checkout.js) using razorpay_key_id + provider_order_id the backend already
 * created; the genuine payment id/order id/signature Razorpay hands back are
 * then sent to POST /payments/{id}/verify, which HMAC-verifies them
 * server-side before transitioning the order. `mock`: TEST MODE bypass
 * buttons — still calls the real verify endpoint, but MockPaymentGateway
 * ignores the fabricated values and just honours force_failure.
 */
export default function PaymentStep({ orderId, paymentId, onSuccess }) {
  const { profile } = useAuth();
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [paying, setPaying] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;
    apiClient.orders
      .get(orderId)
      .then((result) => {
        if (!cancelled) setOrder(result);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiClientError ? e.message : "Could not load this order.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [orderId]);

  const isRazorpay = order?.payment?.provider === "razorpay";

  const finish = (result) => {
    if (result.payment_status === "success") {
      onSuccess(orderId);
    } else {
      setError("Payment was not successful. Please try again.");
    }
  };

  const payWithRazorpay = async () => {
    if (!order?.payment?.razorpay_key_id) return;
    setPaying(true);
    setError(null);
    try {
      const options = {
        key: order.payment.razorpay_key_id,
        amount: order.payment.amount_paise,
        currency: order.currency,
        order_id: order.payment.provider_order_id,
        name: "RiderON",
        description: `Order ${order.booking_reference}`,
        prefill: {
          name: order.sender.name,
          contact: order.sender.phone,
          email: profile?.email || undefined,
        },
        theme: { color: "#ff4f12" },
      };
      const checkoutResult = await openRazorpayCheckoutWeb(options);
      const result = await apiClient.payments.verify(paymentId, {
        razorpay_order_id: checkoutResult.razorpay_order_id,
        razorpay_payment_id: checkoutResult.razorpay_payment_id,
        razorpay_signature: checkoutResult.razorpay_signature,
        force_failure: false,
      });
      finish(result);
    } catch (e) {
      if (e instanceof ApiClientError) {
        setError(e.message);
      } else {
        setError(e?.error?.description ?? e?.description ?? "Payment was cancelled or failed. Please try again.");
      }
    } finally {
      setPaying(false);
    }
  };

  const payWithMock = async (forceFailure) => {
    if (!order?.payment) return;
    setPaying(true);
    setError(null);
    try {
      const result = await apiClient.payments.verify(paymentId, {
        razorpay_order_id: order.payment.provider_order_id,
        razorpay_payment_id: `mock_pay_${Date.now()}`,
        razorpay_signature: `mock_signature_${Date.now()}`,
        force_failure: forceFailure,
      });
      finish(result);
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Payment failed. Please try again.");
    } finally {
      setPaying(false);
    }
  };

  if (loading) {
    return (
      <div className="booking-step">
        <p className="step-note">Loading order…</p>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="booking-step">
        <p className="form-error">{error ?? "Order not found."}</p>
      </div>
    );
  }

  return (
    <div className="booking-step">
      <h2>Complete your payment</h2>
      <div className="payment-card">
        {!isRazorpay ? <span className="test-mode-badge">TEST MODE</span> : null}
        <p className="payment-reference">Order {order.booking_reference}</p>
        <p className="payment-amount">{formatPaise(order.total_amount_paise)}</p>
        <p className="payment-provider">via {order.payment?.provider ?? "mock"} gateway</p>
      </div>

      {error ? <p className="form-error">{error}</p> : null}

      <div className="step-footer">
        {isRazorpay ? (
          <button type="button" className="fare-btn" onClick={payWithRazorpay} disabled={paying}>
            {paying ? "Processing…" : `Pay ${formatPaise(order.total_amount_paise)}`}
          </button>
        ) : (
          <>
            <button type="button" className="fare-btn" onClick={() => payWithMock(false)} disabled={paying}>
              {paying ? "Processing…" : `Pay ${formatPaise(order.total_amount_paise)}`}
            </button>
            <button type="button" className="link-button" onClick={() => (paying ? undefined : payWithMock(true))}>
              Simulate a failed payment (test)
            </button>
          </>
        )}
      </div>
    </div>
  );
}
