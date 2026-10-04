import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { useBookingDraft } from "../../lib/bookingDraft";
import BookingStepper from "../../components/BookingStepper";
import RouteStep from "./steps/RouteStep";
import ParcelStep from "./steps/ParcelStep";
import ContactsStep from "./steps/ContactsStep";
import SummaryStep from "./steps/SummaryStep";
import PaymentStep from "./steps/PaymentStep";

const STEPS = ["route", "parcel", "contacts", "summary", "payment"];
const STEP_LABELS = {
  route: "Route",
  parcel: "Parcel",
  contacts: "Contacts",
  summary: "Review",
  payment: "Payment",
};
const STEPPER_STEPS = STEPS.map((key) => ({ key, label: STEP_LABELS[key] }));

export default function BookingPage() {
  const navigate = useNavigate();
  const { draft, update, reset } = useBookingDraft();
  const [step, setStep] = useState(draft.pendingOrder ? "summary" : "route");
  const [pendingOrderId, setPendingOrderId] = useState(draft.pendingOrder?.id ?? null);
  const [pendingPaymentId, setPendingPaymentId] = useState(draft.pendingOrder?.paymentId ?? null);

  const goTo = (nextStep) => {
    setStep(nextStep);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const onConfirmed = (orderId, paymentId) => {
    setPendingOrderId(orderId);
    setPendingPaymentId(paymentId);
    goTo("payment");
  };

  const onPaid = () => {
    const orderId = pendingOrderId;
    reset();
    navigate(`/orders/${orderId}`, { state: { justBooked: true } });
  };

  const stepIndex = STEPS.indexOf(step);

  return (
    <>
      <Header />
      <main className="booking-page">
        <div className="container booking-page-inner">
          <BookingStepper steps={STEPPER_STEPS} currentIndex={stepIndex} />

          {step === "route" ? <RouteStep draft={draft} update={update} onContinue={() => goTo("parcel")} /> : null}
          {step === "parcel" ? (
            <ParcelStep draft={draft} update={update} onContinue={() => goTo("contacts")} onBack={() => goTo("route")} />
          ) : null}
          {step === "contacts" ? (
            <ContactsStep draft={draft} update={update} onContinue={() => goTo("summary")} onBack={() => goTo("parcel")} />
          ) : null}
          {step === "summary" ? (
            <SummaryStep draft={draft} update={update} onConfirmed={onConfirmed} onBack={() => goTo("contacts")} />
          ) : null}
          {step === "payment" && pendingOrderId && pendingPaymentId ? (
            <PaymentStep orderId={pendingOrderId} paymentId={pendingPaymentId} onSuccess={onPaid} />
          ) : null}
        </div>
      </main>
      <Footer />
    </>
  );
}
