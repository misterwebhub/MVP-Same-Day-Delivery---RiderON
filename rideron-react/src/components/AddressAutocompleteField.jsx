import React, { useEffect, useRef, useState } from "react";
import { Loader2 } from "lucide-react";
import { apiClient } from "../lib/apiClient";

/**
 * Free-text address input that upgrades itself to Google Places Autocomplete
 * (via the backend's /places/* proxy) and silently degrades to a plain text
 * input (no suggestions, no lat/lng) the moment any Places request fails for
 * any reason (backend down, offline, Google quota, no key configured
 * server-side) — address entry must never hard-block on Google being
 * unavailable. Mirrors apps/customer's AddressAutocompleteField.tsx.
 *
 * Routed through our own backend rather than calling Google directly:
 * Google's Autocomplete/Details JSON endpoints don't send CORS headers, so a
 * direct browser fetch would be blocked outright; the proxy also keeps the
 * Google API key server-side instead of in the client bundle.
 */
export function AddressAutocompleteField({
  label,
  value,
  onChangeText,
  onCoordinateResolved,
  placeholder,
  originBias,
}) {
  const [predictions, setPredictions] = useState([]);
  const [searching, setSearching] = useState(false);
  const [resolving, setResolving] = useState(false);
  // Flips permanently once we see any failure — after that this behaves like
  // a plain text field for the rest of its lifetime rather than retrying a
  // broken integration on every keystroke.
  const disabledRef = useRef(false);
  const debounceRef = useRef(null);
  const requestIdRef = useRef(0);
  const containerRef = useRef(null);

  useEffect(() => {
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, []);

  useEffect(() => {
    function onOutsideClick(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setPredictions([]);
      }
    }
    document.addEventListener("mousedown", onOutsideClick);
    return () => document.removeEventListener("mousedown", onOutsideClick);
  }, []);

  const search = (text) => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (disabledRef.current || text.trim().length < 3) {
      setPredictions([]);
      return;
    }
    const thisRequestId = ++requestIdRef.current;
    debounceRef.current = setTimeout(() => {
      setSearching(true);
      apiClient.places
        .autocomplete(text, originBias)
        .then((data) => {
          if (requestIdRef.current !== thisRequestId) return;
          if (Array.isArray(data?.predictions)) {
            setPredictions(
              data.predictions.map((p) => ({
                placeId: p.place_id,
                description: p.description,
              })),
            );
          } else {
            disabledRef.current = true;
            setPredictions([]);
          }
        })
        .catch(() => {
          disabledRef.current = true;
          setPredictions([]);
        })
        .finally(() => {
          if (requestIdRef.current === thisRequestId) setSearching(false);
        });
    }, 350);
  };

  const onSelectPrediction = (prediction) => {
    setPredictions([]);
    onChangeText(prediction.description);
    setResolving(true);
    apiClient.places
      .details(prediction.placeId)
      .then((data) => {
        if (data?.location && typeof data.location.lat === "number" && typeof data.location.lng === "number") {
          onCoordinateResolved?.(data.location.lat, data.location.lng);
        } else {
          disabledRef.current = true;
        }
        // The autocomplete prediction's description is often just a
        // landmark/POI name with no pincode — once Place Details resolves,
        // swap in the full formatted address so the rider gets a
        // deliverable address with a pincode.
        if (data?.formatted_address) {
          onChangeText(data.formatted_address);
        }
      })
      .catch(() => {
        disabledRef.current = true;
      })
      .finally(() => setResolving(false));
  };

  return (
    <div className="address-autocomplete" ref={containerRef}>
      {label ? <span className="field-label">{label}</span> : null}
      <div className="address-autocomplete-input-row">
        <textarea
          rows={2}
          value={value}
          onChange={(event) => {
            onChangeText(event.target.value);
            search(event.target.value);
          }}
          placeholder={placeholder}
        />
        {searching || resolving ? <Loader2 size={16} className="spin-icon" /> : null}
      </div>
      {predictions.length > 0 ? (
        <div className="address-autocomplete-dropdown">
          {predictions.map((prediction) => (
            <button
              type="button"
              key={prediction.placeId}
              className="address-autocomplete-option"
              onClick={() => onSelectPrediction(prediction)}
            >
              {prediction.description}
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
