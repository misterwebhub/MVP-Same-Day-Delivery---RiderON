import React from "react";
import { BadgeIndianRupee, Headphones, MapPin, PackageCheck, ShieldCheck, TrainFront } from "lucide-react";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";

const services = [
  { icon: TrainFront, title: "Station to Station Delivery", text: "Fast parcel movement between Kanpur Central and Lucknow Charbagh." },
  { icon: PackageCheck, title: "Same Day Parcel", text: "Book, drop, and deliver on the same day through express train routes." },
  { icon: MapPin, title: "Parcel Tracking", text: "Track parcel status from pickup station to destination station." },
  { icon: ShieldCheck, title: "Safe Handling", text: "Careful handling for documents, boxes, gifts, and daily business parcels." },
  { icon: BadgeIndianRupee, title: "Transparent Pricing", text: "Clear weight slab pricing with no hidden fees." },
  { icon: Headphones, title: "Customer Support", text: "Call and WhatsApp assistance throughout the delivery flow." }
];

export default function ServicesPage() {
  return (
    <>
      <Header />
      <main className="inner-page">
        <section className="page-hero container">
          <p className="route-label">RIDERON SERVICES</p>
          <h1>Fast parcel services built around daily train routes.</h1>
          <p>Choose RiderON for same-day, safe, and affordable parcel delivery between Kanpur and Lucknow.</p>
        </section>
        <section className="container service-grid">
          {services.map(({ icon: Icon, title, text }) => (
            <article className="service-card" key={title}>
              <Icon />
              <h2>{title}</h2>
              <p>{text}</p>
            </article>
          ))}
        </section>
      </main>
      <Footer />
    </>
  );
}
