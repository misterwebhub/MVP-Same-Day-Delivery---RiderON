import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

/** Gate for any route that requires a logged-in + profile-complete user.
 * Redirects to /login?redirect=<path> (preserving the intended destination)
 * when the session is a plain guest, and shows a lightweight loading state
 * while AuthContext is still resolving the stored session on boot. */
export default function ProtectedRoute({ children }) {
  const { status } = useAuth();
  const location = useLocation();

  if (status === "loading") {
    return (
      <div className="app-loading-screen">
        <div className="spinner" />
      </div>
    );
  }

  if (status !== "authed") {
    const redirect = encodeURIComponent(`${location.pathname}${location.search}`);
    return <Navigate to={`/login?redirect=${redirect}`} replace />;
  }

  return children;
}
