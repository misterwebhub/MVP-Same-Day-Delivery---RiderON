import React from "react";
import { Check } from "lucide-react";

/**
 * Numbered step indicator for the booking flow — completed steps get a green
 * checkmark and a green connector, the current step is highlighted in the
 * brand orange, upcoming steps stay muted grey. Deliberately richer than
 * apps/customer's StepProgress.tsx (a flat colored bar with no checkmarks or
 * labels), per explicit request to design a proper "done" state for the web.
 *
 * Collapses to a compact "Step N of M" readout on narrow viewports via CSS
 * (see .booking-stepper-compact in styles.css) rather than JS media-query
 * logic, so it stays correct across resizes without extra state.
 */
export default function BookingStepper({ steps, currentIndex }) {
  const total = steps.length;
  const current = steps[currentIndex];

  return (
    <nav className="booking-stepper" aria-label="Booking progress">
      <ol className="booking-stepper-list">
        {steps.map((step, index) => {
          const isDone = index < currentIndex;
          const isCurrent = index === currentIndex;
          const state = isDone ? "done" : isCurrent ? "current" : "upcoming";
          return (
            <li key={step.key} className={`booking-stepper-item booking-stepper-item--${state}`}>
              <div className="booking-stepper-node">
                <span className="booking-stepper-circle" aria-hidden="true">
                  {isDone ? <Check size={14} strokeWidth={3} /> : index + 1}
                </span>
                <span className="booking-stepper-label">{step.label}</span>
              </div>
              {index < total - 1 ? (
                <span className={`booking-stepper-connector booking-stepper-connector--${isDone ? "done" : "upcoming"}`} aria-hidden="true" />
              ) : null}
            </li>
          );
        })}
      </ol>
      <p className="booking-stepper-compact">
        Step {currentIndex + 1} of {total} — <strong>{current?.label}</strong>
      </p>
    </nav>
  );
}
