import React, { useEffect, useState } from "react";
import { ArrowLeftRight, Navigation } from "lucide-react";
import { ApiClientError } from "@rideron/api-client";
import { apiClient } from "../../../lib/apiClient";
import { nextDays, toYMD, formatDateLabel } from "../../../utils/date";
import { formatPaise } from "../../../utils/currency";
import { isManualAddressCity } from "../../../lib/manualAddressCities";
import { ManualAddressCard, isManualAddressValid } from "../../../components/ManualAddressCard";

function StationSelect({
  label,
  cities,
  cityId,
  onCityChange,
  stations,
  stationId,
  onStationChange,
  loadingStations,
  hideStationPicker,
  autoStation,
}) {
  return (
    <div className="station-select">
      <span className="field-label">{label}</span>
      <select value={cityId ?? ""} onChange={(event) => onCityChange(event.target.value ? Number(event.target.value) : null)}>
        <option value="">Select city</option>
        {cities.map((city) => (
          <option key={city.id} value={city.id}>
            {city.name}
          </option>
        ))}
      </select>
      {hideStationPicker ? (
        cityId ? (
          <p className="step-note auto-station-note">
            {loadingStations
              ? "Finding your local hub…"
              : autoStation
                ? `We'll route this via ${autoStation.name} — enter your exact address below.`
                : "No local hub configured for this city yet."}
          </p>
        ) : null
      ) : (
        <select
          value={stationId ?? ""}
          onChange={(event) => onStationChange(event.target.value ? Number(event.target.value) : null)}
          disabled={!cityId || loadingStations}
        >
          <option value="">{loadingStations ? "Loading stations…" : "Select station"}</option>
          {stations.map((station) => (
            <option key={station.id} value={station.id}>
              {station.name} ({station.code})
            </option>
          ))}
        </select>
      )}
    </div>
  );
}

export default function RouteStep({ draft, update, onContinue }) {
  const [cities, setCities] = useState([]);
  const [originStations, setOriginStations] = useState([]);
  const [destinationStations, setDestinationStations] = useState([]);
  const [loadingOriginStations, setLoadingOriginStations] = useState(false);
  const [loadingDestinationStations, setLoadingDestinationStations] = useState(false);

  const [originCityId, setOriginCityId] = useState(draft.originCity?.id ?? null);
  const [originStationId, setOriginStationId] = useState(draft.originStation?.id ?? null);
  const [destinationCityId, setDestinationCityId] = useState(draft.destinationCity?.id ?? null);
  const [destinationStationId, setDestinationStationId] = useState(draft.destinationStation?.id ?? null);

  const [route, setRoute] = useState(draft.route);
  const [resolving, setResolving] = useState(false);
  const [routeError, setRouteError] = useState(null);

  const [bookingDate, setBookingDate] = useState(draft.bookingDate);
  const [schedules, setSchedules] = useState([]);
  const [loadingSchedules, setLoadingSchedules] = useState(false);
  const [scheduleId, setScheduleId] = useState(draft.schedule?.route_schedule_id ?? null);

  const [pickupAddress, setPickupAddress] = useState({
    text: draft.pickupAddressText || "",
    latitude: draft.pickupLatitude ?? null,
    longitude: draft.pickupLongitude ?? null,
    postalCode: draft.pickupPostalCode ?? null,
  });
  const [deliveryAddress, setDeliveryAddress] = useState({
    text: draft.deliveryAddressText || "",
    latitude: draft.deliveryLatitude ?? null,
    longitude: draft.deliveryLongitude ?? null,
    postalCode: draft.deliveryPostalCode ?? null,
  });

  useEffect(() => {
    apiClient.catalog.listCities().then(setCities).catch(() => setCities([]));
  }, []);

  useEffect(() => {
    if (!originCityId) {
      setOriginStations([]);
      return;
    }
    setLoadingOriginStations(true);
    apiClient.catalog
      .listStations(originCityId)
      .then(setOriginStations)
      .catch(() => setOriginStations([]))
      .finally(() => setLoadingOriginStations(false));
  }, [originCityId]);

  useEffect(() => {
    if (!destinationCityId) {
      setDestinationStations([]);
      return;
    }
    setLoadingDestinationStations(true);
    apiClient.catalog
      .listStations(destinationCityId)
      .then(setDestinationStations)
      .catch(() => setDestinationStations([]))
      .finally(() => setLoadingDestinationStations(false));
  }, [destinationCityId]);

  useEffect(() => {
    if (!originStationId || !destinationStationId) {
      setRoute(null);
      return;
    }
    if (originStationId === destinationStationId) {
      setRoute(null);
      setRouteError("Pickup and drop-off stations must be different.");
      return;
    }
    setResolving(true);
    setRouteError(null);
    apiClient.catalog
      .findRoute({ origin_station_id: originStationId, destination_station_id: destinationStationId })
      .then((result) => setRoute(result))
      .catch((e) => {
        setRoute(null);
        setRouteError(e instanceof ApiClientError ? e.message : "No route found between these stations.");
      })
      .finally(() => setResolving(false));
  }, [originStationId, destinationStationId]);

  // Per product direction, the slot picker itself is removed from the
  // customer-facing UI — we still fetch real schedule/capacity data
  // (required by PricingEngine.quote()/OrderController.store()) and
  // auto-pick the earliest non-full departure for the selected date,
  // showing only an instructional note. Mirrors apps/customer's TimeSlot.tsx.
  useEffect(() => {
    if (!route || !bookingDate) {
      setSchedules([]);
      setScheduleId(null);
      return;
    }
    setLoadingSchedules(true);
    apiClient.catalog
      .getRouteSchedules(route.id, bookingDate)
      .then((result) => {
        setSchedules(result);
        const earliestAvailable = result
          .filter((s) => s.seats_available > 0)
          .sort((a, b) => a.departure_time.localeCompare(b.departure_time))[0];
        setScheduleId(earliestAvailable ? earliestAvailable.route_schedule_id : null);
      })
      .catch(() => {
        setSchedules([]);
        setScheduleId(null);
      })
      .finally(() => setLoadingSchedules(false));
  }, [route?.id, bookingDate]);

  const onSwap = () => {
    const oc = originCityId;
    const os = originStationId;
    setOriginCityId(destinationCityId);
    setOriginStationId(destinationStationId);
    setDestinationCityId(oc);
    setDestinationStationId(os);
  };

  const originCity = cities.find((c) => c.id === originCityId) || null;
  const destinationCity = cities.find((c) => c.id === destinationCityId) || null;
  const originStation = originStations.find((s) => s.id === originStationId) || draft.originStation;
  const destinationStation = destinationStations.find((s) => s.id === destinationStationId) || draft.destinationStation;
  const schedule = schedules.find((s) => s.route_schedule_id === scheduleId) || null;

  const pickupNeedsAddress = isManualAddressCity(originCity?.name);
  const deliveryNeedsAddress = isManualAddressCity(destinationCity?.name);

  // Manual-address cities (Kanpur today) have a single local hub — skip the
  // station dropdown entirely and auto-pick it once stations load, so the
  // customer goes straight to "use current location / enter address" instead
  // of choosing from a list of one. Mirrors StationPickerModal.tsx's
  // auto-confirm behavior for these cities.
  useEffect(() => {
    if (pickupNeedsAddress && !loadingOriginStations && originStations.length > 0 && !originStationId) {
      setOriginStationId(originStations[0].id);
    }
  }, [pickupNeedsAddress, originStations, loadingOriginStations, originStationId]);
  useEffect(() => {
    if (deliveryNeedsAddress && !loadingDestinationStations && destinationStations.length > 0 && !destinationStationId) {
      setDestinationStationId(destinationStations[0].id);
    }
  }, [deliveryNeedsAddress, destinationStations, loadingDestinationStations, destinationStationId]);

  // Clear any stale captured address the moment the city no longer opts into
  // manual entry (e.g. the customer changed their mind on the origin city) —
  // mirrors StationPickerModal.tsx clearing the capture on a non-opted-in pick.
  useEffect(() => {
    if (!pickupNeedsAddress) {
      setPickupAddress({ text: "", latitude: null, longitude: null, postalCode: null });
    }
  }, [pickupNeedsAddress]);
  useEffect(() => {
    if (!deliveryNeedsAddress) {
      setDeliveryAddress({ text: "", latitude: null, longitude: null, postalCode: null });
    }
  }, [deliveryNeedsAddress]);

  const canContinue = Boolean(
    route &&
      bookingDate &&
      schedule &&
      (!pickupNeedsAddress || isManualAddressValid(pickupAddress)) &&
      (!deliveryNeedsAddress || isManualAddressValid(deliveryAddress)),
  );

  const submit = () => {
    if (!canContinue) return;
    update({
      originCity,
      originStation,
      destinationCity,
      destinationStation,
      route,
      bookingDate,
      schedule,
      pickupAddressText: pickupNeedsAddress ? pickupAddress.text.trim() : "",
      pickupLatitude: pickupNeedsAddress ? pickupAddress.latitude : null,
      pickupLongitude: pickupNeedsAddress ? pickupAddress.longitude : null,
      pickupPostalCode: pickupNeedsAddress ? pickupAddress.postalCode : null,
      deliveryAddressText: deliveryNeedsAddress ? deliveryAddress.text.trim() : "",
      deliveryLatitude: deliveryNeedsAddress ? deliveryAddress.latitude : null,
      deliveryLongitude: deliveryNeedsAddress ? deliveryAddress.longitude : null,
      deliveryPostalCode: deliveryNeedsAddress ? deliveryAddress.postalCode : null,
    });
    onContinue();
  };

  return (
    <div className="booking-step">
      <h2>Where's it going?</h2>
      <p className="step-subtitle">Pick a pickup and a drop-off station — we'll find your route.</p>

      <div className="route-fields-card">
        <div className="station-field-group">
          <StationSelect
            label="From"
            cities={cities}
            cityId={originCityId}
            onCityChange={(id) => {
              setOriginCityId(id);
              setOriginStationId(null);
            }}
            stations={originStations}
            stationId={originStationId}
            onStationChange={setOriginStationId}
            loadingStations={loadingOriginStations}
            hideStationPicker={pickupNeedsAddress}
            autoStation={originStation}
          />
          {pickupNeedsAddress ? (
            <ManualAddressCard
              title="Pickup address"
              allowCurrentLocation
              station={originStation}
              value={pickupAddress}
              onChange={setPickupAddress}
            />
          ) : null}
        </div>
        <button type="button" className="swap-btn" onClick={onSwap} aria-label="Swap stations">
          <ArrowLeftRight size={16} />
        </button>
        <div className="station-field-group">
          <StationSelect
            label="To"
            cities={cities}
            cityId={destinationCityId}
            onCityChange={(id) => {
              setDestinationCityId(id);
              setDestinationStationId(null);
            }}
            stations={destinationStations}
            stationId={destinationStationId}
            onStationChange={setDestinationStationId}
            loadingStations={loadingDestinationStations}
            hideStationPicker={deliveryNeedsAddress}
            autoStation={destinationStation}
          />
          {deliveryNeedsAddress ? (
            <ManualAddressCard
              title="Delivery address"
              allowCurrentLocation={false}
              station={destinationStation}
              value={deliveryAddress}
              onChange={setDeliveryAddress}
            />
          ) : null}
        </div>
      </div>

      {resolving ? <p className="step-note">Checking route…</p> : null}
      {routeError ? <p className="form-error">{routeError}</p> : null}
      {route && !resolving ? (
        <div className="route-preview">
          <Navigation size={16} />
          <span>
            {route.distance_km} km · ~{route.estimated_duration_minutes} mins · from {formatPaise(route.price_from)}
          </span>
        </div>
      ) : null}

      {route ? (
        <div className="date-schedule-card">
          <span className="field-label">Pickup date</span>
          <div className="date-chip-row">
            {nextDays(7).map((d) => {
              const ymd = toYMD(d);
              return (
                <button
                  key={ymd}
                  type="button"
                  className={`date-chip${bookingDate === ymd ? " active" : ""}`}
                  onClick={() => setBookingDate(ymd)}
                >
                  {formatDateLabel(ymd)}
                </button>
              );
            })}
          </div>

          {bookingDate ? (
            <>
              {loadingSchedules ? <p className="step-note">Finding your departure…</p> : null}
              {!loadingSchedules && !schedule ? <p className="form-error">No departures available for this date — try another date.</p> : null}
              {!loadingSchedules && schedule ? (
                <p className="step-note auto-station-note">
                  Your parcel will be picked up on the selected date and dispatched on the next available ride — we'll
                  notify you once a rider accepts.
                </p>
              ) : null}
            </>
          ) : null}
        </div>
      ) : null}

      <div className="step-footer">
        <button type="button" className="fare-btn" disabled={!canContinue} onClick={submit}>
          Continue
        </button>
      </div>
    </div>
  );
}
