import React from "react";
import { Mail, MapPin, Phone } from "lucide-react";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";

export default function ContactPage() {
  return (
    <>
      <Header />
      <main className="inner-page">
        <section className="page-hero container">
          <p className="route-label">CONTACT RIDERON</p>
          <h1>Need help booking or tracking a parcel?</h1>
          <p>Reach our team for parcel booking, delivery status, pricing, and station support.</p>
        </section>
        <section className="container contact-layout">
          <div className="contact-panel">
            <h2>Contact Details</h2>
            <p><Phone /> +91 700 123 4567</p>
            <p><Mail /> support@rideron.in</p>
            <p><MapPin /> Kanpur, Uttar Pradesh</p>
          </div>
          <form className="contact-form">
            <label>Name<input type="text" placeholder="Your name" /></label>
            <label>Phone<input type="tel" placeholder="+91" /></label>
            <label>Message<textarea placeholder="How can we help?" rows="5" /></label>
            <button type="button" className="fare-btn">Send Message</button>
          </form>
        </section>
      </main>
      <Footer />
    </>
  );
}
