import React from "react";
import { CircleDollarSign, Radar, ShieldCheck } from "lucide-react";
import Feature from "../../../components/Feature";
import SectionLabel from "../../../components/SectionLabel";

export default function Hero() {
  return (
    <section className="hero" id="about-us">
      <div className="container hero-grid">
        <div className="hero-content">
          <SectionLabel>ABOUT RIDERON</SectionLabel>
          <h1>
            Delivering Trust,
            <br />
            Connecting Cities.
          </h1>
          <p className="hero-lead">
            RiderON is a station-to-station parcel delivery service connecting
            Kanpur to Lucknow with speed, safety and affordability. We use the
            power of Indian Railways to make your deliveries faster and more reliable.
          </p>

          <div className="orange-line" />

          <div className="hero-features">
            <Feature icon={<ShieldCheck />} title="Safe & Secure" text="Your parcels are in safe hands." />
            <Feature icon={<Radar />} title="Fast Delivery" text="Same-day delivery via express trains." />
            <Feature icon={<CircleDollarSign />} title="Affordable" text="Best prices with no hidden charges." />
          </div>
        </div>

        <div className="hero-visual">
          <div className="hero-image image-placeholder rider-image">
            <div className="image-badge">RIDERON</div>
          </div>
        </div>
      </div>
    </section>
  );
}
