import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { ApiClientError } from "@rideron/api-client";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { apiClient } from "../../lib/apiClient";
import { useAuth } from "../../context/AuthContext";

export default function ProfilePage() {
  const { profile, refreshProfile, logout } = useAuth();
  const navigate = useNavigate();

  const [name, setName] = useState(profile?.name ?? "");
  const [email, setEmail] = useState(profile?.email ?? "");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [saved, setSaved] = useState(false);

  const [contacts, setContacts] = useState([]);
  const [loadingContacts, setLoadingContacts] = useState(true);
  const [newContact, setNewContact] = useState({ type: "sender", label: "", name: "", phone: "", landmark: "" });
  const [addingContact, setAddingContact] = useState(false);

  const loadContacts = () => {
    setLoadingContacts(true);
    apiClient.profile
      .listSavedContacts()
      .then(setContacts)
      .catch(() => setContacts([]))
      .finally(() => setLoadingContacts(false));
  };

  useEffect(() => {
    loadContacts();
  }, []);

  const onSaveProfile = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError(null);
    setSaved(false);
    try {
      await apiClient.profile.update({ name, email: email || null });
      await refreshProfile();
      setSaved(true);
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not save your profile.");
    } finally {
      setSaving(false);
    }
  };

  const onAddContact = async (event) => {
    event.preventDefault();
    if (!newContact.name.trim() || newContact.phone.length !== 10) {
      setError("Enter a name and 10-digit phone number for the contact.");
      return;
    }
    setAddingContact(true);
    setError(null);
    try {
      await apiClient.profile.createSavedContact(newContact);
      setNewContact({ type: "sender", label: "", name: "", phone: "", landmark: "" });
      loadContacts();
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not save contact.");
    } finally {
      setAddingContact(false);
    }
  };

  const onDeleteContact = async (contactId) => {
    if (!window.confirm("Remove this saved contact?")) return;
    try {
      await apiClient.profile.deleteSavedContact(contactId);
      setContacts((prev) => prev.filter((c) => c.id !== contactId));
    } catch (e) {
      window.alert(e instanceof ApiClientError ? e.message : "Could not remove contact.");
    }
  };

  const onLogout = async () => {
    await logout();
    navigate("/");
  };

  return (
    <>
      <Header />
      <main className="account-page">
        <div className="container">
          <h1>My Profile</h1>

          <form onSubmit={onSaveProfile} className="summary-card profile-form">
            <label className="form-field">
              <span className="field-label">Name</span>
              <input type="text" value={name} onChange={(event) => setName(event.target.value)} />
            </label>
            <label className="form-field">
              <span className="field-label">Email</span>
              <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} />
            </label>
            <label className="form-field">
              <span className="field-label">Phone</span>
              <input type="text" value={profile?.phone ? `+91 ${profile.phone}` : ""} disabled />
            </label>
            {error ? <p className="form-error">{error}</p> : null}
            {saved ? <p className="form-info">Profile updated.</p> : null}
            <button type="submit" className="fare-btn" disabled={saving}>
              {saving ? "Saving…" : "Save changes"}
            </button>
          </form>

          <div className="summary-card">
            <h3>Saved Contacts</h3>
            {loadingContacts ? <p className="step-note">Loading…</p> : null}
            {!loadingContacts && contacts.length === 0 ? <p className="step-note">No saved contacts yet.</p> : null}
            <div className="saved-contact-list">
              {contacts.map((contact) => (
                <div key={contact.id} className="saved-contact-row">
                  <div>
                    <strong>{contact.label || contact.name}</strong>
                    <span>{contact.name} · +91 {contact.phone}</span>
                  </div>
                  <button type="button" className="link-button" onClick={() => onDeleteContact(contact.id)}>
                    Remove
                  </button>
                </div>
              ))}
            </div>

            <form onSubmit={onAddContact} className="add-contact-form">
              <div className="form-row">
                <label className="form-field">
                  <span className="field-label">Type</span>
                  <select value={newContact.type} onChange={(event) => setNewContact({ ...newContact, type: event.target.value })}>
                    <option value="sender">Sender</option>
                    <option value="receiver">Receiver</option>
                  </select>
                </label>
                <label className="form-field">
                  <span className="field-label">Label (optional)</span>
                  <input type="text" value={newContact.label} onChange={(event) => setNewContact({ ...newContact, label: event.target.value })} />
                </label>
              </div>
              <div className="form-row">
                <label className="form-field">
                  <span className="field-label">Name</span>
                  <input type="text" value={newContact.name} onChange={(event) => setNewContact({ ...newContact, name: event.target.value })} />
                </label>
                <label className="form-field">
                  <span className="field-label">Phone</span>
                  <input
                    type="tel"
                    maxLength={10}
                    value={newContact.phone}
                    onChange={(event) => setNewContact({ ...newContact, phone: event.target.value.replace(/\D/g, "") })}
                  />
                </label>
              </div>
              <button type="submit" className="ghost-btn" disabled={addingContact}>
                {addingContact ? "Adding…" : "Add contact"}
              </button>
            </form>
          </div>

          <button type="button" className="ghost-btn logout-btn" onClick={onLogout}>
            Log out
          </button>
        </div>
      </main>
      <Footer />
    </>
  );
}
