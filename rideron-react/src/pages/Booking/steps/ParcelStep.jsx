import React, { useState } from "react";
import { PARCEL_TYPE_LABELS, WEIGHT_SLAB_LABELS } from "@rideron/types";

const PARCEL_TYPES = Object.keys(PARCEL_TYPE_LABELS);
const WEIGHT_SLABS = Object.keys(WEIGHT_SLAB_LABELS);
// Mirrors apps/customer's ParcelDetails.tsx MAX_QUANTITY.
const MAX_QUANTITY = 10;

export default function ParcelStep({ draft, update, onContinue, onBack }) {
  const [parcelType, setParcelType] = useState(draft.parcelType);
  const [weightSlab, setWeightSlab] = useState(draft.weightSlab);
  const [quantity, setQuantity] = useState(draft.quantity || 1);
  const [declaredValue, setDeclaredValue] = useState(draft.declaredValuePaise ? draft.declaredValuePaise / 100 : "");
  const [specialInstructions, setSpecialInstructions] = useState(draft.specialInstructions || "");
  const [photoUri, setPhotoUri] = useState(draft.parcelPhotoUri);
  const [photoFile, setPhotoFile] = useState(draft.parcelPhotoFile);
  const [error, setError] = useState(null);

  const onPhotoChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    if (photoUri) URL.revokeObjectURL(photoUri);
    setPhotoFile(file);
    setPhotoUri(URL.createObjectURL(file));
  };

  // Mirrors apps/customer's ParcelDetails.tsx onContinue() validation: a
  // declared value of 0 is allowed (only empty/negative/NaN is rejected).
  const declaredValueValid = declaredValue !== "" && !Number.isNaN(Number(declaredValue)) && Number(declaredValue) >= 0;
  const canContinue = parcelType && weightSlab && quantity > 0 && quantity <= MAX_QUANTITY && declaredValueValid && photoUri;

  const submit = () => {
    if (!canContinue) {
      setError("Fill in parcel type, weight, declared value and add a photo before continuing.");
      return;
    }
    update({
      parcelType,
      weightSlab,
      quantity: Number(quantity),
      declaredValuePaise: Math.round(Number(declaredValue) * 100),
      specialInstructions,
      parcelPhotoUri: photoUri,
      parcelPhotoFile: photoFile,
    });
    onContinue();
  };

  return (
    <div className="booking-step">
      <h2>Parcel details</h2>
      <p className="step-subtitle">Tell us what you're sending so we can quote a price.</p>

      <label className="form-field">
        <span className="field-label">Parcel Type</span>
        <select value={parcelType ?? ""} onChange={(event) => setParcelType(event.target.value || null)}>
          <option value="">Select type</option>
          {PARCEL_TYPES.map((type) => (
            <option key={type} value={type}>
              {PARCEL_TYPE_LABELS[type]}
            </option>
          ))}
        </select>
      </label>

      <label className="form-field">
        <span className="field-label">Weight</span>
        <select value={weightSlab ?? ""} onChange={(event) => setWeightSlab(event.target.value || null)}>
          <option value="">Select weight</option>
          {WEIGHT_SLABS.map((slab) => (
            <option key={slab} value={slab}>
              {WEIGHT_SLAB_LABELS[slab]}
            </option>
          ))}
        </select>
      </label>

      <div className="form-row">
        <label className="form-field">
          <span className="field-label">Quantity</span>
          <input
            type="number"
            min="1"
            max={MAX_QUANTITY}
            value={quantity}
            onChange={(event) => {
              const next = Number(event.target.value);
              if (Number.isNaN(next)) return;
              setQuantity(Math.min(MAX_QUANTITY, Math.max(1, next)));
            }}
          />
        </label>
        <label className="form-field">
          <span className="field-label">Declared Value (₹)</span>
          <input type="number" min="1" value={declaredValue} onChange={(event) => setDeclaredValue(event.target.value)} placeholder="e.g. 500" />
        </label>
      </div>

      <label className="form-field">
        <span className="field-label">Special instructions (optional)</span>
        <textarea rows="3" value={specialInstructions} onChange={(event) => setSpecialInstructions(event.target.value)} placeholder="Anything the rider should know" />
      </label>

      <label className="form-field">
        <span className="field-label">Parcel photo (required)</span>
        <input type="file" accept="image/*" onChange={onPhotoChange} />
        {photoUri ? <img src={photoUri} alt="Parcel preview" className="photo-preview" /> : null}
      </label>

      {error ? <p className="form-error">{error}</p> : null}

      <div className="step-footer">
        <button type="button" className="ghost-btn" onClick={onBack}>
          Back
        </button>
        <button type="button" className="fare-btn" onClick={submit}>
          Continue
        </button>
      </div>
    </div>
  );
}
