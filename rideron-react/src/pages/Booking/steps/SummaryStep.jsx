import React, { useEffect, useState } from "react";
import { PARCEL_TYPE_LABELS, WEIGHT_SLAB_LABELS, INVOICE_REQUIRED_ABOVE_PAISE } from "@rideron/types";
import { ApiClientError } from "@rideron/api-client";
import { apiClient } from "../../../lib/apiClient";
import { formatPaise } from "../../../utils/currency";
import { formatDateLabel } from "../../../utils/date";

function Row({ label, value }) {
  return (
    <div className="summary-row">
      <span>{label}</span>
      <strong>{value}</strong>
    </div>
  );
}

/** Review + server-quoted price + prohibited-items declaration, then Confirm &
 * Pay. Mirrors apps/customer's BookingSummary.tsx, including its retry-safe
 * onConfirm(): resume a pending order (persisted in the draft) instead of
 * calling orders.create() again if an earlier attempt got partway through. */
export default function SummaryStep({ draft, update, onConfirmed, onBack }) {
  const { route, schedule, sender, receiver } = draft;
  const [quoteLoading, setQuoteLoading] = useState(true);
  const [error, setError] = useState(null);
  const [prohibitedItems, setProhibitedItems] = useState([]);
  const [showProhibited, setShowProhibited] = useState(false);
  const [accepted, setAccepted] = useState(draft.prohibitedItemsAccepted);
  const [submitting, setSubmitting] = useState(false);
  const [invoicePhotoUri, setInvoicePhotoUri] = useState(draft.invoicePhotoUri);
  const [invoicePhotoFile, setInvoicePhotoFile] = useState(draft.invoicePhotoFile);

  const invoiceRequired = draft.declaredValuePaise > INVOICE_REQUIRED_ABOVE_PAISE;

  const onInvoicePhotoChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    if (invoicePhotoUri) URL.revokeObjectURL(invoicePhotoUri);
    const uri = URL.createObjectURL(file);
    setInvoicePhotoFile(file);
    setInvoicePhotoUri(uri);
    update({ invoicePhotoUri: uri, invoicePhotoFile: file });
  };

  useEffect(() => {
    apiClient.catalog
      .getProhibitedItems()
      .then((result) => setProhibitedItems(result.items))
      .catch(() => {});
  }, []);

  useEffect(() => {
    if (!route || !schedule || !draft.weightSlab) {
      setError("Missing route or slot — please go back and select them again.");
      setQuoteLoading(false);
      return;
    }
    let cancelled = false;
    setQuoteLoading(true);
    setError(null);
    apiClient.pricing
      .getQuote({
        route_id: route.id,
        route_schedule_id: schedule.route_schedule_id,
        weight_slab: draft.weightSlab,
        quantity: draft.quantity,
        declared_value_paise: draft.declaredValuePaise,
        coupon_code: null,
        door_pickup: draft.doorPickup,
      })
      .then((quote) => {
        if (!cancelled) update({ quote });
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiClientError ? e.message : "Could not get a price quote.");
      })
      .finally(() => {
        if (!cancelled) setQuoteLoading(false);
      });
    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [route?.id, schedule?.route_schedule_id, draft.weightSlab, draft.quantity, draft.declaredValuePaise, draft.doorPickup]);

  const quote = draft.quote;

  const onConfirm = async () => {
    if (!accepted) {
      setError("Please confirm your parcel doesn't contain prohibited items.");
      return;
    }
    if (!quote || !draft.bookingDate || !draft.parcelType) {
      setError("Something's missing from your booking — please review the previous steps.");
      return;
    }
    if (!draft.parcelPhotoUri) {
      setError("A parcel photo is required — go back to Parcel Details to add one.");
      return;
    }
    if (invoiceRequired && !invoicePhotoUri) {
      setError("A bill/invoice photo is required for declared values above ₹1000 — add one below before continuing.");
      return;
    }
    setSubmitting(true);
    setError(null);
    update({ prohibitedItemsAccepted: accepted });
    try {
      let order;
      if (draft.pendingOrder) {
        // Resume a previous attempt instead of creating a duplicate order —
        // orders.create() mints a fresh Idempotency-Key on every call, so
        // re-calling it here would NOT be deduped by the backend.
        order = await apiClient.orders.get(draft.pendingOrder.id);
      } else {
        order = await apiClient.orders.create({
          quote_token: quote.quote_token,
          booking_date: draft.bookingDate,
          sender_name: sender.name,
          sender_phone: sender.phone,
          sender_landmark: sender.landmark || null,
          receiver_name: receiver.name,
          receiver_phone: receiver.phone,
          receiver_landmark: receiver.landmark || null,
          pickup_address_text: draft.pickupAddressText || null,
          pickup_latitude: draft.pickupLatitude,
          pickup_longitude: draft.pickupLongitude,
          pickup_postal_code: draft.pickupPostalCode,
          delivery_address_text: draft.deliveryAddressText || null,
          delivery_latitude: draft.deliveryLatitude,
          delivery_longitude: draft.deliveryLongitude,
          delivery_postal_code: draft.deliveryPostalCode,
          parcel_type: draft.parcelType,
          special_instructions: draft.specialInstructions || null,
          prohibited_items_accepted: true,
        });
        if (!order.payment) {
          setError("Order created but no payment was set up — please contact support.");
          setSubmitting(false);
          return;
        }
        update({ pendingOrder: { id: order.id, paymentId: order.payment.id } });
      }
      if (!order.payment) {
        setError("Order created but no payment was set up — please contact support.");
        setSubmitting(false);
        return;
      }

      if (draft.parcelPhotoFile && order.parcel?.photos.length === 0) {
        try {
          const objectUrl = URL.createObjectURL(draft.parcelPhotoFile);
          await apiClient.orders.uploadParcelPhoto(order.id, {
            uri: objectUrl,
            name: draft.parcelPhotoFile.name || "parcel-photo.jpg",
            type: draft.parcelPhotoFile.type || "image/jpeg",
          });
          URL.revokeObjectURL(objectUrl);
        } catch (e) {
          setError(e instanceof ApiClientError ? e.message : "Could not upload your parcel photo. Please try again.");
          setSubmitting(false);
          return;
        }
      }

      if (invoiceRequired && invoicePhotoFile && !(order.parcel?.invoice_photos?.length > 0)) {
        try {
          const objectUrl = URL.createObjectURL(invoicePhotoFile);
          await apiClient.orders.uploadParcelPhoto(
            order.id,
            {
              uri: objectUrl,
              name: invoicePhotoFile.name || "invoice-photo.jpg",
              type: invoicePhotoFile.type || "image/jpeg",
            },
            "invoice",
          );
          URL.revokeObjectURL(objectUrl);
        } catch (e) {
          setError(e instanceof ApiClientError ? e.message : "Could not upload your invoice photo. Please try again.");
          setSubmitting(false);
          return;
        }
      }

      onConfirmed(order.id, order.payment.id);
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not create the order. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="booking-step">
      <h2>Review & confirm</h2>

      <div className="summary-card">
        <h3>Route</h3>
        <Row label="From" value={route?.origin_station?.name ?? "—"} />
        {draft.pickupAddressText ? (
          <Row
            label="Pickup address"
            value={draft.pickupPostalCode ? `${draft.pickupAddressText} — ${draft.pickupPostalCode}` : draft.pickupAddressText}
          />
        ) : null}
        <Row label="To" value={route?.destination_station?.name ?? "—"} />
        {draft.deliveryAddressText ? (
          <Row
            label="Delivery address"
            value={draft.deliveryPostalCode ? `${draft.deliveryAddressText} — ${draft.deliveryPostalCode}` : draft.deliveryAddressText}
          />
        ) : null}
        <Row label="Pickup date" value={draft.bookingDate ? formatDateLabel(draft.bookingDate) : "—"} />
        <Row label="Time slot" value={schedule ? `${schedule.departure_time.slice(0, 5)} – ${schedule.arrival_time.slice(0, 5)}` : "—"} />
      </div>

      <div className="summary-card">
        <h3>Parcel</h3>
        <Row label="Type" value={draft.parcelType ? PARCEL_TYPE_LABELS[draft.parcelType] : "—"} />
        <Row label="Weight" value={draft.weightSlab ? WEIGHT_SLAB_LABELS[draft.weightSlab] : "—"} />
        <Row label="Quantity" value={String(draft.quantity)} />
        <Row label="Declared value" value={formatPaise(draft.declaredValuePaise)} />
        {draft.parcelPhotoUri ? <img src={draft.parcelPhotoUri} alt="Parcel" className="photo-preview" /> : null}
        {invoiceRequired ? (
          <div className="invoice-block">
            <span className="field-label">Bill/invoice photo (required above ₹1000 declared value)</span>
            <input type="file" accept="image/*" onChange={onInvoicePhotoChange} />
            {invoicePhotoUri ? <img src={invoicePhotoUri} alt="Invoice preview" className="photo-preview" /> : null}
          </div>
        ) : null}
      </div>

      <div className="summary-card">
        <h3>Door Pickup</h3>
        <label className="checkbox-field">
          <input
            type="checkbox"
            checked={draft.doorPickup}
            onChange={(event) => update({ doorPickup: event.target.checked })}
          />
          <span>Have the rider collect your parcel from your door</span>
        </label>
      </div>

      <div className="summary-card">
        <h3>Sender</h3>
        <Row label="Name" value={sender.name || "—"} />
        <Row label="Phone" value={sender.phone ? `+91 ${sender.phone}` : "—"} />
      </div>

      <div className="summary-card">
        <h3>Receiver</h3>
        <Row label="Name" value={receiver.name || "—"} />
        <Row label="Phone" value={receiver.phone ? `+91 ${receiver.phone}` : "—"} />
      </div>

      <div className="summary-card">
        <h3>Price</h3>
        {quoteLoading ? <p className="step-note">Loading quote…</p> : null}
        {!quoteLoading && quote
          ? quote.breakdown.map((line) => (
              <Row key={line.label} label={line.label} value={`${line.amount_paise < 0 ? "− " : ""}${formatPaise(Math.abs(line.amount_paise))}`} />
            ))
          : null}
        <div className="summary-row summary-total">
          <span>Final</span>
          <strong>{quote ? formatPaise(quote.total_amount_paise) : "—"}</strong>
        </div>
      </div>

      <button type="button" className="link-button" onClick={() => setShowProhibited((s) => !s)}>
        {showProhibited ? "Hide" : "View"} prohibited items list
      </button>
      {showProhibited ? (
        <ul className="prohibited-list">
          {prohibitedItems.length === 0 ? <li>Loading…</li> : prohibitedItems.map((item) => <li key={item.id}>{item.name}</li>)}
        </ul>
      ) : null}

      <label className="checkbox-field">
        <input type="checkbox" checked={accepted} onChange={(event) => setAccepted(event.target.checked)} />
        <span>I confirm this parcel does not contain any prohibited or restricted items.</span>
      </label>

      {error ? <p className="form-error">{error}</p> : null}

      <div className="step-footer">
        <button type="button" className="ghost-btn" onClick={onBack} disabled={submitting}>
          Back
        </button>
        <button
          type="button"
          className="fare-btn"
          onClick={onConfirm}
          disabled={submitting || quoteLoading || !quote || !accepted || (invoiceRequired && !invoicePhotoUri)}
        >
          {submitting ? "Processing…" : "Confirm & Pay"}
        </button>
      </div>
    </div>
  );
}
