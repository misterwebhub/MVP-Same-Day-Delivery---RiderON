import React, { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { UserCheck } from "lucide-react";
import { ApiClientError } from "@rideron/api-client";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { useAuth } from "../../context/AuthContext";

export default function CompleteProfilePage() {
  const { completeProfile } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const redirect = searchParams.get("redirect") || "";

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const onSubmit = async (event) => {
    event.preventDefault();
    if (!name.trim()) {
      setError("Please enter your name.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      await completeProfile(name.trim(), email.trim());
      navigate(redirect || "/orders");
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not save your profile. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <Header />
      <main className="auth-page">
        <div className="auth-card">
          <p className="route-label">ALMOST THERE</p>
          <h1>Complete your profile</h1>
          <p className="auth-subtitle">Just a couple of details to finish setting up your account.</p>
          <form onSubmit={onSubmit} className="auth-form">
            <label>
              Full Name
              <input type="text" placeholder="Your name" value={name} onChange={(event) => setName(event.target.value)} autoFocus />
            </label>
            <label>
              Email (optional)
              <input type="email" placeholder="you@example.com" value={email} onChange={(event) => setEmail(event.target.value)} />
            </label>
            {error ? <p className="form-error">{error}</p> : null}
            <button type="submit" className="fare-btn auth-submit" disabled={submitting}>
              <UserCheck size={16} /> {submitting ? "Saving…" : "Finish Setup"}
            </button>
          </form>
        </div>
      </main>
      <Footer />
    </>
  );
}
