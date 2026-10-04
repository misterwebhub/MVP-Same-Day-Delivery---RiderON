import React from "react";
import { ArrowRight, Package, TrainFront } from "lucide-react";

export default function CTA() {
  return (
    <section className="container cta" id="book-parcel">
      <div className="cta-copy">
        <h2>Ready to Send Your Parcel?</h2>
        <p>Experience fast, safe and same-day parcel delivery between Kanpur and Lucknow with RiderON.</p>
        <a href="#book-parcel" className="primary-btn">
          Book Your Parcel Now <ArrowRight size={17} />
        </a>
      </div>
      <div className="cta-art">
        <TrainFront size={150} strokeWidth={1} />
        <Package size={58} strokeWidth={1.5} />
        <Package size={42} strokeWidth={1.5} />
      </div>
    </section>
  );
}
