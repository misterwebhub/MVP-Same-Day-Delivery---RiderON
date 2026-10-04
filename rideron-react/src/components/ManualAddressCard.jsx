import React, { useState } from "react";
import { CheckCircle2, Loader2, LocateFixed } from "lucide-react";
import { apiClient } from "../lib/apiClient";
import { AddressAutocompleteField } from "./AddressAutocompleteField";

/** India Post PIN codes are exactly 6 digits, first digit 1-9. */
const PINCODE_PATTERN = /^[1-9][0-9]{5}$/;

/**
 * Inline "enter exact address" card shown when a picked station's city opted
 * into manual address entry (currently Kanpur — see
 * ../lib/manualAddressCities.js). Mirrors the address step inside
 * apps/customer's StationPickerModal.tsx: an optional "use my current
 * location" shortcut (pickup leg only), an address-autocomplete text field,
 * and a required 6-digit pincode — plus a lightweight map preview once a
 * coordinate is resolved, which the RN app doesn't have room for but this
 * website can show for free reassurance.
 */
export function ManualAddressCard({ title, allowCurrentLocation, station, value, onChange }) {
  const [locating, setLocating] = useState(false);
  const [locateFailed, setLocateFailed] = useState(false);

  const { text, latitude, longitude, postalCode } = value;
  const pincodeValid = postalCode !== null && postalCode !== undefined && PINCODE_PATTERN.test(String(postalCode).trim());

  const onUseCurrentLocation = () => {
    if (!("geolocation" in navigator)) {
      setLocateFailed(true);
      return;
    }
    setLocating(true);
    setLocateFailed(false);
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude: lat, longitude: lng } = position.coords;
        let next = { ...value, latitude: lat, longitude: lng };
        try {
          const geocoded = await apiClient.places.reverseGeocode(lat, lng);
          if (geocoded?.formatted_address) {
            next = { ...next, text: geocoded.formatted_address, postalCode: geocoded.postal_code ?? null };
          }
        } catch {
          // Coordinate is still captured even if reverse-geocoding fails —
          // the customer can type the address manually below.
        }
        onChange(next);
        setLocating(false);
      },
      () => {
        setLocateFailed(true);
        setLocating(false);
      },
      { enableHighAccuracy: false, timeout: 10000 },
    );
  };

  return (
    <div className="manual-address-card">
      <h3>{title}</h3>
      <p className="step-note">
        Tell us exactly where to {allowCurrentLocation ? "come" : "deliver"} — pincode is required, address helps the rider.
      </p>

      {allowCurrentLocation ? (
        <>
          <button type="button" className="locate-btn" onClick={onUseCurrentLocation} disabled={locating}>
            <span className="locate-btn-icon">
              {locating ? <Loader2 size={18} className="spin-icon" /> : <LocateFixed size={18} />}
            </span>
            <span className="locate-btn-text">
              <strong>{latitude !== null ? "Location found — tap to refresh" : "Use my current location"}</strong>
              <small>
                {locating
                  ? "Finding you…"
                  : latitude !== null
                    ? postalCode
                      ? `Pincode ${postalCode} detected — edit below if needed`
                      : "Detected — edit the address below if needed"
                    : "We'll auto-fill the address and pincode nearby"}
              </small>
            </span>
            {latitude !== null ? <CheckCircle2 size={20} className="locate-btn-check" /> : null}
          </button>
          {locateFailed ? <p className="form-error">Couldn't detect your location — type the address below instead.</p> : null}
          <div className="or-divider">
            <span />
            <small>OR SEARCH MANUALLY</small>
            <span />
          </div>
        </>
      ) : null}

      <AddressAutocompleteField
        label="Address"
        value={text ?? ""}
        onChangeText={(text) => onChange({ ...value, text, postalCode: null })}
        onCoordinateResolved={(lat, lng) => onChange({ ...value, latitude: lat, longitude: lng })}
        originBias={
          latitude !== null && longitude !== null
            ? { latitude, longitude }
            : station
              ? { latitude: Number(station.latitude), longitude: Number(station.longitude) }
              : undefined
        }
        placeholder="Search or type House / street / area"
      />

      <label className="form-field pincode-field">
        <span className="field-label">Pincode *</span>
        <input
          type="text"
          inputMode="numeric"
          maxLength={6}
          value={postalCode ?? ""}
          onChange={(event) => onChange({ ...value, postalCode: event.target.value.replace(/[^0-9]/g, "").slice(0, 6) })}
          placeholder="6-digit pincode"
          className={postalCode && !pincodeValid ? "input-error" : undefined}
        />
        {postalCode && !pincodeValid ? <small className="form-error">Enter a valid 6-digit pincode.</small> : null}
      </label>

      {latitude !== null && longitude !== null ? (
        <div className="map-preview">
          <iframe
            title={`${title} location preview`}
            src={`https://www.google.com/maps?q=${latitude},${longitude}&output=embed`}
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
          />
        </div>
      ) : null}
    </div>
  );
}

export function isManualAddressValid(value) {
  return Boolean(value.postalCode) && PINCODE_PATTERN.test(String(value.postalCode).trim());
}
