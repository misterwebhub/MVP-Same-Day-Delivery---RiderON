import React, { useCallback, useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { ArrowLeft, Check, Copy } from "lucide-react";
import { ORDER_STATUS_SELF_SERVICE_CANCELLABLE } from "@rideron/types";
import { ApiClientError } from "@rideron/api-client";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { apiClient } from "../../lib/apiClient";
import { formatPaise } from "../../utils/currency";
import { formatDateLabel } from "../../utils/date";

const STATUS_SEQUENCE = [
  "PAYMENT_PENDING",
  "BOOKED",
  "RIDER_ASSIGNMENT_PENDING",
  "RIDER_ASSIGNED",
  "WAITING_FOR_PICKUP",
  "RIDER_ARRIVED_PICKUP",
  "PICKUP_OTP_PENDING",
  "PICKED_UP",
  "IN_TRANSIT",
  "ARRIVED_DESTINATION",
  "WAITING_FOR_RECEIVER",
  "DELIVERY_OTP_PENDING",
  "DELIVERED",
  "COMPLETED",
];

const MILESTONES = [
  { status: "BOOKED", label: "Booked" },
  { status: "RIDER_ASSIGNED", label: "Rider assigned" },
  { status: "PICKED_UP", label: "Picked up" },
  { status: "IN_TRANSIT", label: "In transit" },
  { status: "DELIVERED", label: "Delivered" },
  { status: "COMPLETED", label: "Completed" },
];

const BRANCH_STATUS_TEXT = {
  PAYMENT_FAILED: "Payment failed for this order.",
  CANCELLED: "This order was cancelled.",
  REFUND_PENDING: "A refund is being processed for this order.",
  REFUNDED: "This order was refunded.",
  FAILED_DELIVERY: "Delivery could not be completed.",
  DISPUTED: "This order is under dispute review.",
};

const BOOKED_INDEX = STATUS_SEQUENCE.indexOf("BOOKED");
const DELIVERED_INDEX = STATUS_SEQUENCE.indexOf("DELIVERED");
function showOtpCards(sequenceIndex) {
  return sequenceIndex >= BOOKED_INDEX && sequenceIndex < DELIVERED_INDEX;
}

function Row({ label, value }) {
  return (
    <div className="summary-row">
      <span>{label}</span>
      <strong>{value}</strong>
    </div>
  );
}

function OtpCard({ title, hint, otp }) {
  const [copied, setCopied] = useState(false);
  const verified = otp?.status === "verified";
  const expired = otp?.status === "expired";

  const onCopy = async () => {
    if (!otp?.code) return;
    try {
      await navigator.clipboard.writeText(otp.code);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      // Non-critical — clipboard access may be unavailable.
    }
  };

  return (
    <div className="otp-card">
      <div className="otp-card-header">
        <h3>{title}</h3>
        {verified ? (
          <span className="otp-verified-pill">
            <Check size={13} /> Verified
          </span>
        ) : null}
      </div>
      {verified ? null : otp?.code ? (
        <>
          <div className="otp-code-box">
            <span className="otp-code">{otp.code}</span>
            <button type="button" className="otp-copy-btn" onClick={onCopy} aria-label="Copy OTP">
              <Copy size={14} /> {copied ? "Copied" : "Copy"}
            </button>
          </div>
          <p className="step-note">{hint}</p>
        </>
      ) : (
        <p className="step-note">
          {expired ? "This code has expired — ask your rider to generate a new one." : "Sent via SMS. Only your rider can regenerate this code if it's lost."}
        </p>
      )}
    </div>
  );
}

export default function OrderDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const orderId = Number(id);
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cancelling, setCancelling] = useState(false);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);

  const load = useCallback(async () => {
    try {
      const result = await apiClient.orders.get(orderId);
      setOrder(result);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not load this order.");
    }
  }, [orderId]);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

  const onRefresh = async () => {
    setLoading(true);
    await load();
    setLoading(false);
  };

  const onCancel = async () => {
    if (!window.confirm("Cancel this order? This cannot be undone.")) return;
    setCancelling(true);
    try {
      const updated = await apiClient.orders.cancel(orderId, {});
      setOrder(updated);
    } catch (e) {
      window.alert(e instanceof ApiClientError ? e.message : "Could not cancel. Please try again.");
    } finally {
      setCancelling(false);
    }
  };

  const onAddPhoto = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    setUploadingPhoto(true);
    const objectUrl = URL.createObjectURL(file);
    try {
      const updated = await apiClient.orders.uploadParcelPhoto(orderId, {
        uri: objectUrl,
        name: file.name || "parcel-photo.jpg",
        type: file.type || "image/jpeg",
      });
      setOrder(updated);
    } catch (e) {
      window.alert(e instanceof ApiClientError ? e.message : "Upload failed. Please try again.");
    } finally {
      URL.revokeObjectURL(objectUrl);
      setUploadingPhoto(false);
    }
  };

  if (loading) {
    return (
      <>
        <Header />
        <main className="account-page">
          <div className="container">
            <p className="step-note">Loading order…</p>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  if (!order) {
    return (
      <>
        <Header />
        <main className="account-page">
          <div className="container">
            <p className="form-error">{error ?? "Order not found."}</p>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  const branchText = BRANCH_STATUS_TEXT[order.status];
  const sequenceIndex = STATUS_SEQUENCE.indexOf(order.status);
  const cancellable = ORDER_STATUS_SELF_SERVICE_CANCELLABLE.includes(order.status);

  return (
    <>
      <Header />
      <main className="account-page">
        <div className="container order-detail">
          <button type="button" className="back-link" onClick={() => navigate("/orders")}>
            <ArrowLeft size={16} /> Back to my bookings
          </button>
          <div className="account-page-header">
            <h1>{order.booking_reference}</h1>
            <button type="button" className="refresh-btn" onClick={onRefresh}>
              Refresh
            </button>
          </div>
          <span className="status-pill">{order.status.replace(/_/g, " ")}</span>

          {branchText ? (
            <div className="status-banner">
              <p>{branchText}</p>
              {order.cancellation_reason ? <small>{order.cancellation_reason}</small> : null}
            </div>
          ) : (
            <div className="timeline-card">
              {MILESTONES.map((m) => {
                const milestoneIndex = STATUS_SEQUENCE.indexOf(m.status);
                const reached = sequenceIndex >= 0 && sequenceIndex >= milestoneIndex;
                return (
                  <div key={m.status} className={`timeline-row${reached ? " reached" : ""}`}>
                    <span className="timeline-dot" />
                    <span>{m.label}</span>
                  </div>
                );
              })}
            </div>
          )}

          {showOtpCards(sequenceIndex) ? (
            <OtpCard title="Your OTP" hint="Read this out to the rider when they collect the parcel." otp={order.pickup_otp} />
          ) : null}
          {showOtpCards(sequenceIndex) ? (
            <OtpCard title="Receiver OTP" hint="Share this with the receiver — they give it to the rider at delivery." otp={order.delivery_otp} />
          ) : null}

          <div className="summary-card">
            <h3>Route</h3>
            <Row label="From" value={order.route?.origin_station?.name ?? "—"} />
            <Row label="To" value={order.route?.destination_station?.name ?? "—"} />
            <Row label="Pickup date" value={order.booking_date ? formatDateLabel(order.booking_date) : "—"} />
            <Row
              label="Time slot"
              value={order.route_schedule ? `${order.route_schedule.departure_time.slice(0, 5)} – ${order.route_schedule.arrival_time.slice(0, 5)}` : "—"}
            />
          </div>

          {order.partner ? (
            <div className="summary-card">
              <h3>Your rider</h3>
              <Row label="Name" value={order.partner.name ?? "—"} />
              {order.partner.vehicle_type ? <Row label="Vehicle" value={order.partner.vehicle_type.replace(/_/g, " ")} /> : null}
              {order.partner.phone ? (
                <a className="ghost-btn" href={`tel:${order.partner.phone}`}>
                  Call {order.partner.name ?? "rider"}
                </a>
              ) : null}
            </div>
          ) : null}

          {order.parcel ? (
            <div className="summary-card">
              <h3>Parcel</h3>
              <Row label="Type" value={order.parcel.parcel_type} />
              <Row label="Weight" value={order.parcel.weight_slab} />
              <Row label="Quantity" value={String(order.parcel.quantity)} />
              <Row label="Declared value" value={formatPaise(order.parcel.declared_value_paise)} />
              {order.parcel.special_instructions ? <Row label="Notes" value={order.parcel.special_instructions} /> : null}
              {order.parcel.photos.length > 0 ? (
                <div className="photo-row">
                  {order.parcel.photos.map((url) => (
                    <a key={url} href={url} target="_blank" rel="noreferrer">
                      <img src={url} alt="Parcel" className="photo-thumb" />
                    </a>
                  ))}
                </div>
              ) : null}
              {sequenceIndex >= 0 && sequenceIndex < STATUS_SEQUENCE.indexOf("PICKED_UP") ? (
                <label className="ghost-btn photo-upload-btn">
                  {uploadingPhoto ? "Uploading…" : "Add parcel photo"}
                  <input type="file" accept="image/*" onChange={onAddPhoto} hidden />
                </label>
              ) : null}
            </div>
          ) : null}

          <div className="summary-card">
            <h3>Sender</h3>
            <Row label="Name" value={order.sender.name} />
            <Row label="Phone" value={`+91 ${order.sender.phone}`} />
          </div>

          <div className="summary-card">
            <h3>Receiver</h3>
            <Row label="Name" value={order.receiver.name} />
            <Row label="Phone" value={`+91 ${order.receiver.phone}`} />
          </div>

          <div className="summary-card">
            <h3>Price</h3>
            {order.price_breakdown.map((line) => (
              <Row key={line.label} label={line.label} value={formatPaise(line.amount_paise)} />
            ))}
            <div className="summary-row summary-total">
              <span>Total</span>
              <strong>{formatPaise(order.total_amount_paise)}</strong>
            </div>
          </div>

          {error ? <p className="form-error">{error}</p> : null}

          {cancellable ? (
            <button type="button" className="ghost-btn" onClick={onCancel} disabled={cancelling}>
              {cancelling ? "Cancelling…" : "Cancel order"}
            </button>
          ) : null}
        </div>
      </main>
      <Footer />
    </>
  );
}
