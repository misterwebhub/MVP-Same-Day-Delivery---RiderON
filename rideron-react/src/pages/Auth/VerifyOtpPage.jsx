import React, { useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { ShieldCheck } from "lucide-react";
import { ApiClientError } from "@rideron/api-client";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { useAuth } from "../../context/AuthContext";

export default function VerifyOtpPage() {
  const { verifyOtp, requestOtp } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const phone = searchParams.get("phone") || "";
  const redirect = searchParams.get("redirect") || "";

  const [otp, setOtp] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [resending, setResending] = useState(false);
  const [error, setError] = useState(null);
  const [info, setInfo] = useState(null);

  if (!phone) {
    return (
      <>
        <Header />
        <main className="auth-page">
          <div className="auth-card">
            <p className="form-error">Missing phone number — please start over.</p>
            <Link to="/login" className="fare-btn auth-submit">Back to login</Link>
          </div>
        </main>
        <Footer />
      </>
    );
  }

  const onSubmit = async (event) => {
    event.preventDefault();
    if (otp.trim().length < 4) {
      setError("Enter the OTP you received.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const result = await verifyOtp(phone, otp.trim());
      if (result.is_new_user) {
        navigate(`/complete-profile${redirect ? `?redirect=${encodeURIComponent(redirect)}` : ""}`);
      } else {
        navigate(redirect || "/orders");
      }
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Invalid or expired OTP. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  const onResend = async () => {
    setResending(true);
    setError(null);
    setInfo(null);
    try {
      await requestOtp(phone);
      setInfo("A new OTP has been sent.");
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : "Could not resend OTP.");
    } finally {
      setResending(false);
    }
  };

  return (
    <>
      <Header />
      <main className="auth-page">
        <div className="auth-card">
          <p className="route-label">VERIFY OTP</p>
          <h1>Enter the OTP</h1>
          <p className="auth-subtitle">We sent a code to +91 {phone}.</p>
          <form onSubmit={onSubmit} className="auth-form">
            <label>
              One-Time Password
              <input
                type="text"
                inputMode="numeric"
                maxLength={6}
                placeholder="••••••"
                value={otp}
                onChange={(event) => setOtp(event.target.value.replace(/\D/g, ""))}
                autoFocus
                className="otp-input"
              />
            </label>
            {error ? <p className="form-error">{error}</p> : null}
            {info ? <p className="form-info">{info}</p> : null}
            <button type="submit" className="fare-btn auth-submit" disabled={submitting}>
              <ShieldCheck size={16} /> {submitting ? "Verifying…" : "Verify & Continue"}
            </button>
          </form>
          <p className="auth-footnote">
            Didn't get a code?{" "}
            <button type="button" className="link-button" onClick={onResend} disabled={resending}>
              {resending ? "Resending…" : "Resend OTP"}
            </button>
          </p>
          <p className="auth-footnote">
            <Link to="/login">Change number</Link>
          </p>
        </div>
      </main>
      <Footer />
    </>
  );
}
