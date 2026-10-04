import React from "react";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";

export default function AboutPage() {
  return (
    <>
      <Header />
      <main className="inner-page">
        <section className="page-hero container">
          <p className="route-label">ABOUT RIDERON</p>
          <h1>Delivering trust and connecting cities through rail-based parcel delivery.</h1>
          <p>
            RiderON is a station-to-station parcel delivery service connecting Kanpur to Lucknow with speed,
            safety, and affordability. We use the power of Indian Railways to make deliveries faster and more reliable.
          </p>
        </section>
        <section className="container about-grid">
          <article>
            <h2>Our Mission</h2>
            <p>To provide fast, safe, and affordable parcel delivery services from Kanpur to Lucknow and beyond.</p>
          </article>
          <article>
            <h2>Our Vision</h2>
            <p>To become India&apos;s most trusted rail-based delivery network connecting every major city.</p>
          </article>
          <article>
            <h2>Our Values</h2>
            <p>Customer first, transparent pricing, reliable delivery, safe handling, and continuous improvement.</p>
          </article>
        </section>
        <section className="container story-band">
          <div className="story-visual-panel" />
          <div>
            <h2>How It All Started</h2>
            <p>
              We noticed parcel delivery between Kanpur and Lucknow was often too slow, expensive, or unreliable.
              RiderON was born with a simple idea: make parcel delivery as fast and reliable as train travel.
            </p>
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}
