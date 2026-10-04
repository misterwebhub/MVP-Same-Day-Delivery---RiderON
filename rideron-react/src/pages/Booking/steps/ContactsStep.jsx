import React, { useEffect, useState } from "react";
import { apiClient } from "../../../lib/apiClient";
import { useAuth } from "../../../context/AuthContext";

function PartyForm({ title, value, onChange, savedContacts, myDetails, onUseMyDetails }) {
  return (
    <div className="contact-form-card">
      <div className="contact-form-card-header">
        <h3>{title}</h3>
        {myDetails ? (
          <button type="button" className="link-button" onClick={onUseMyDetails}>
            Use my details
          </button>
        ) : null}
      </div>
      {savedContacts.length > 0 ? (
        <div className="saved-contacts-row">
          {savedContacts.map((contact) => (
            <button
              key={contact.id}
              type="button"
              className="saved-contact-chip"
              onClick={() => onChange({ name: contact.name, phone: contact.phone, landmark: contact.landmark || "" })}
            >
              {contact.label || contact.name}
            </button>
          ))}
        </div>
      ) : null}
      <label className="form-field">
        <span className="field-label">Name</span>
        <input type="text" value={value.name} onChange={(event) => onChange({ ...value, name: event.target.value })} />
      </label>
      <label className="form-field">
        <span className="field-label">Phone</span>
        <div className="phone-input">
          <span>+91</span>
          <input
            type="tel"
            inputMode="numeric"
            maxLength={10}
            value={value.phone}
            onChange={(event) => onChange({ ...value, phone: event.target.value.replace(/\D/g, "") })}
          />
        </div>
      </label>
      <label className="form-field">
        <span className="field-label">Landmark (optional)</span>
        <input type="text" value={value.landmark} onChange={(event) => onChange({ ...value, landmark: event.target.value })} />
      </label>
    </div>
  );
}

export default function ContactsStep({ draft, update, onContinue, onBack }) {
  const { profile } = useAuth();
  const [sender, setSender] = useState(draft.sender);
  const [receiver, setReceiver] = useState(draft.receiver);
  const [savedContacts, setSavedContacts] = useState([]);
  const [error, setError] = useState(null);

  useEffect(() => {
    apiClient.profile
      .listSavedContacts()
      .then(setSavedContacts)
      .catch(() => setSavedContacts([]));
  }, []);

  const myDetails = profile
    ? { name: profile.name || "", phone: (profile.phone || "").replace(/\D/g, "").slice(-10) }
    : null;

  // Auto-fill the sender with the logged-in customer's own details — this is
  // almost always who's actually sending the parcel, same assumption the
  // mobile app's booking flow makes. Only fills blank fields so it never
  // clobbers something the customer already typed or picked from a saved
  // contact, and re-applies if the profile finishes loading after this step
  // has already mounted.
  useEffect(() => {
    if (!myDetails) return;
    setSender((current) => {
      if (current.name.trim() || current.phone) return current;
      return { ...current, name: myDetails.name, phone: myDetails.phone };
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [profile]);

  const valid = (party) => party.name.trim().length > 0 && party.phone.length === 10;

  const submit = () => {
    if (!valid(sender) || !valid(receiver)) {
      setError("Enter a name and 10-digit phone number for both sender and receiver.");
      return;
    }
    update({ sender, receiver });
    onContinue();
  };

  return (
    <div className="booking-step">
      <h2>Sender & receiver</h2>
      <p className="step-subtitle">Who's sending this and who'll receive it?</p>

      <PartyForm
        title="Sender"
        value={sender}
        onChange={setSender}
        savedContacts={savedContacts.filter((c) => c.type !== "receiver")}
        myDetails={myDetails}
        onUseMyDetails={() => setSender((current) => ({ ...current, name: myDetails.name, phone: myDetails.phone }))}
      />
      <PartyForm title="Receiver" value={receiver} onChange={setReceiver} savedContacts={savedContacts.filter((c) => c.type !== "sender")} />

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
