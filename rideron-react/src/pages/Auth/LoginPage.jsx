import React, { useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { Phone } from "lucide-react";
import { ApiClientError } from "@rideron/api-client";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { useAuth } from "../../context/AuthContext";

export default function LoginPage() {
  const { requestOtp } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const redirect = searchParams.get("redirect") || "";

  const [phone, setPhone] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const onSubmit = async (event) => {
    event.preventDefault();
    const digits = phone.replace(/\D/g, "");
    if (digits.length !== 10) {
      setError("Enter a valid 10-digit mobile number.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      await requestOtp(digits);
      navigate(`/verify-otp?phone=${digits}${redirect ? `&redirect=${encodeURIComponent(redirect)}` : ""}`);
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not send OTP. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <Header />
      <main className="auth-page">
        <div className="auth-card">
          <p className="route-label">LOGIN</p>
          <h1>Log in to RiderON</h1>
          <p className="auth-subtitle">We'll text you a one-time code to verify your number.</p>
          <form onSubmit={onSubmit} className="auth-form">
            <label>
              Mobile Number
              <div className="phone-input">
                <span>+91</span>
                <input
                  type="tel"
                  inputMode="numeric"
                  maxLength={10}
                  placeholder="98765 43210"
                  value={phone}
                  onChange={(event) => setPhone(event.target.value.replace(/\D/g, ""))}
                  autoFocus
                />
              </div>
            </label>
            {error ? <p className="form-error">{error}</p> : null}
            <button type="submit" className="fare-btn auth-submit" disabled={submitting}>
              <Phone size={16} /> {submitting ? "Sending OTP…" : "Send OTP"}
            </button>
          </form>
          <p className="auth-footnote">
            By continuing you agree to RiderON's Terms &amp; Conditions and Privacy Policy.
          </p>
          <p className="auth-footnote">
            Just want to browse? <Link to="/">Go back home</Link>
          </p>
        </div>
      </main>
      <Footer />
    </>
  );
}
