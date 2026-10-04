import React from "react";
import { BrowserRouter, Route, Routes } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import ProtectedRoute from "./routes/ProtectedRoute";

import HomePage from "./pages/Home/HomePage";
import AboutPage from "./pages/About/AboutPage";
import ContactPage from "./pages/Contact/ContactPage";
import ServicesPage from "./pages/Services/ServicesPage";

import LoginPage from "./pages/Auth/LoginPage";
import VerifyOtpPage from "./pages/Auth/VerifyOtpPage";
import CompleteProfilePage from "./pages/Auth/CompleteProfilePage";

import BookingPage from "./pages/Booking/BookingPage";

import OrdersListPage from "./pages/Account/OrdersListPage";
import OrderDetailPage from "./pages/Account/OrderDetailPage";
import ProfilePage from "./pages/Account/ProfilePage";

import TrackParcelPage from "./pages/Track/TrackParcelPage";

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/about" element={<AboutPage />} />
          <Route path="/services" element={<ServicesPage />} />
          <Route path="/contact" element={<ContactPage />} />

          <Route path="/login" element={<LoginPage />} />
          <Route path="/verify-otp" element={<VerifyOtpPage />} />
          <Route path="/complete-profile" element={<CompleteProfilePage />} />

          <Route
            path="/book"
            element={
              <ProtectedRoute>
                <BookingPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/orders"
            element={
              <ProtectedRoute>
                <OrdersListPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/orders/:id"
            element={
              <ProtectedRoute>
                <OrderDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile"
            element={
              <ProtectedRoute>
                <ProfilePage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/track"
            element={
              <ProtectedRoute>
                <TrackParcelPage />
              </ProtectedRoute>
            }
          />

          <Route path="*" element={<HomePage />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
