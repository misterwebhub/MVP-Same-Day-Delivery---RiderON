import React from "react";
import {
  ArrowLeftRight,
  ArrowRight,
  BadgeIndianRupee,
  Box,
  CheckCircle2,
  CircleDot,
  ClipboardEdit,
  Headphones,
  MapPin,
  PackageCheck,
  Plane,
  Rocket,
  ShieldCheck,
  Star,
  Target,
  TrainFront,
  Zap
} from "lucide-react";
import { Link } from "react-router-dom";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";

const parcelTypes = [
  { value: "documents", label: "Documents" },
  { value: "clothing", label: "Clothing" },
  { value: "electronics", label: "Electronics" },
  { value: "gifts", label: "Gifts" },
  { value: "books", label: "Books" },
  { value: "other", label: "Other" }
];

// Mirrors backend/database/seeders/PricingRuleSeeder.php & config('pricing')
// — base fee + weight-slab fee, then platform fee (2%) and GST (18%) added
// on top of that subtotal. Parcel type itself carries no extra fee; keep
// these numbers in sync if the seeder/pricing rules ever change.
const BASE_FARE_RUPEES = 50;
const PLATFORM_FEE_PERCENT = 2;
const GST_PERCENT = 18;

const weightSlabs = [
  { value: "upto_100g", label: "Up to 100 g", slabFare: 99 },
  { value: "upto_1kg", label: "101 g - 1 kg", slabFare: 149 },
  { value: "upto_2kg", label: "1.1 - 2 kg", slabFare: 249 }
];

function estimateTotalRupees(slabFare) {
  const subtotal = BASE_FARE_RUPEES + slabFare;
  const platformFee = (subtotal * PLATFORM_FEE_PERCENT) / 100;
  const gst = (subtotal * GST_PERCENT) / 100;
  return Math.round(subtotal + platformFee + gst);
}

const benefits = [
  { icon: Rocket, title: "Same Day Delivery", text: "Your parcel reaches Lucknow on the same day via train." },
  { icon: ShieldCheck, title: "Safe & Secure", text: "We ensure 100% safe handling of your valuable parcels." },
  //{ icon: TrainFront, title: "Train Based Delivery", text: "Fast & reliable delivery using daily express train services." },
  { icon: MapPin, title: "Live Tracking", text: "Track your parcel in real time from Kanpur to Lucknow." },
  { icon: BadgeIndianRupee, title: "Affordable Pricing", text: "Best rates with no hidden charges. Transparent pricing." },
  { icon: Headphones, title: "Customer Support", text: "We are here to help you on WhatsApp & call." }
];

const steps = [
  { icon: ClipboardEdit, title: "Book Online", text: "Enter parcel details and book online in seconds." },
  { icon: Box, title: "Drop Parcel", text: "Drop your parcel at Kanpur Central station." },
  { icon: TrainFront, title: "We Deliver", text: "We deliver your parcel to Lucknow by the next available train." },
  { icon: PackageCheck, title: "Collect at Station", text: "Receiver collects the parcel from Lucknow Charbagh station." }
];

const testimonials = [
  { name: "Ankit Sharma", text: "Super fast service! My parcel reached Lucknow within 3 hours. Highly recommended." },
  { name: "Neha Verma", text: "Very reliable and safe delivery. Packaging and handling is excellent." },
  { name: "Mohd. Arif", text: "Best parcel delivery between Kanpur and Lucknow. Affordable and trustworthy." }
];

function BookingCard() {
  const [reversed, setReversed] = React.useState(false);
  const [parcelType, setParcelType] = React.useState("");
  const [weight, setWeight] = React.useState("");
  const [fare, setFare] = React.useState(null);

  const calculateFare = (event) => {
    event.preventDefault();
    if (!parcelType || !weight) {
      setFare({ error: "Select a parcel type and weight to calculate your fare." });
      return;
    }

    const selectedType = parcelTypes.find((type) => type.value === parcelType);
    const selectedWeight = weightSlabs.find((slab) => slab.value === weight);

    setFare({
      amount: estimateTotalRupees(selectedWeight.slabFare),
      type: selectedType.label,
      weight: selectedWeight.label
    });
  };

  const from = reversed
    ? { label: "Lucknow Charbagh (LKO)", note: null }
    : { label: "Kanpur", note: "no station required — doorstep pickup" };
  const to = reversed
    ? { label: "Kanpur", note: "no station required — doorstep drop" }
    : { label: "Lucknow Charbagh (LKO)", note: null };

  return (
    <section className="booking-card container" id="book-parcel">
      <div className="booking-card-heading">
        <h2>Calculate Your Fare</h2>
        <button
          type="button"
          className="route-swap-btn"
          onClick={() => setReversed((current) => !current)}
          aria-label="Swap route direction"
        >
          <ArrowLeftRight size={15} /> Swap route
        </button>
      </div>
      <form className="booking-grid" onSubmit={calculateFare}>
        <label className="booking-field">
          <span>From Station</span>
          <strong><MapPin size={15} />{from.label}</strong>
          {from.note ? <em className="booking-field-note">{from.note}</em> : null}
        </label>
        <label className="booking-field">
          <span>To Station</span>
          <strong><MapPin size={15} />{to.label}</strong>
          {to.note ? <em className="booking-field-note">{to.note}</em> : null}
        </label>
        <label className="booking-field">
          <span>Parcel Type</span>
          <select aria-label="Parcel Type" value={parcelType} onChange={(event) => setParcelType(event.target.value)}>
            <option value="">Select Type</option>
            {parcelTypes.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}
          </select>
        </label>
        <label className="booking-field">
          <span>Weight</span>
          <select aria-label="Weight" value={weight} onChange={(event) => setWeight(event.target.value)}>
            <option value="">Select Weight</option>
            {weightSlabs.map((slab) => <option key={slab.value} value={slab.value}>{slab.label}</option>)}
          </select>
        </label>
        <button className="fare-btn" type="submit">
          Check Fare <ArrowRight size={17} />
        </button>
      </form>
      {fare && (
        <div className={`fare-result${fare.error ? " fare-error" : ""}`} aria-live="polite">
          {fare.error || (
            <>
              <strong>Estimated fare: Rs {fare.amount}</strong>
              <span>{fare.type}, {fare.weight} · {reversed ? "Lucknow to Kanpur" : "Kanpur to Lucknow"} · incl. platform fee &amp; GST</span>
              <Link to="/book" className="fare-cta-link">Continue to booking <ArrowRight size={14} /></Link>
            </>
          )}
        </div>
      )}
      <p className="booking-note">
        <CircleDot size={13} /> Currently delivering both ways between Kanpur and Lucknow Charbagh (LKO) — Kanpur pickup/drop is doorstep, no station needed
      </p>
    </section>
  );
}

function HomeHero() {
  return (
    <section className="home-hero">
      <div className="container home-hero-inner">
        <div className="hero-copy">
          <p className="route-label">KANPUR TO LUCKNOW</p>
          <h1>
            Same-Day Parcel
            <br />
            Delivery You Can
            <br />
            <span>Trust!</span>
          </h1>
          <p className="hero-subtitle">
            Fast, safe & affordable station to station parcel delivery between Kanpur & Lucknow.
          </p>
          <div className="trust-row">
            <span><ShieldCheck size={18} /> 100% Secure</span>
            <span><Zap size={18} /> Same Day Delivery</span>
            <span><MapPin size={18} /> Live Tracking</span>
          </div>
          <div className="hero-buttons">
            <Link to="/book" className="primary-btn"><Box size={18} /> Book Your Parcel</Link>
            <Link to="/track" className="ghost-btn"><MapPin size={18} /> Track Your Parcel</Link>
          </div>
        </div>
        <div className="hero-scene" aria-hidden="true">
          <Plane className="plane-art" />
          <div className="train-art">
            <div className="train-window">KANPUR - LUCKNOW</div>
          </div>
          <div className="bus-art">RIDERON</div>
          <div className="rider-art">
            <div className="helmet" />
            <div className="bag">R</div>
          </div>
        </div>
      </div>
    </section>
  );
}

function Benefits() {
  return (
    <section className="container section-tight">
      <h2 className="center-title">Why Choose <span>RiderON?</span></h2>
      <div className="home-benefit-grid">
        {benefits.map(({ icon: Icon, title, text }) => (
          <article className="home-benefit-card" key={title}>
            <Icon />
            <h3>{title}</h3>
            <p>{text}</p>
          </article>
        ))}
      </div>
    </section>
  );
}

function HowItWorks() {
  return (
    <section className="container how-section">
      <div className="line-title"><span /> HOW IT WORKS <span /></div>
      <div className="steps-grid">
        {steps.map(({ icon: Icon, title, text }, index) => (
          <article className="step-card" key={title}>
            <b>{index + 1}</b>
            <div className="step-icon"><Icon /></div>
            <h3>{title}</h3>
            <p>{text}</p>
          </article>
        ))}
      </div>
    </section>
  );
}

function RoutePricing() {
  return (
    <section className="container route-pricing" id="pricing">
      <div className="delivery-route">
        <h3>Our Delivery Route</h3>
        <div className="route-map">
          <div className="station-photo">KNP</div>
          <div className="route-dash" />
          <div className="route-train"><TrainFront /></div>
          <div className="route-dash" />
          <div className="station-photo lucknow">LKO</div>
        </div>
        <div className="route-labels">
          <strong>KANPUR</strong>
          <strong>2 - 4 HOURS</strong>
          <strong>LUCKNOW CHARBAGH (LKO)</strong>
        </div>
        <div className="route-direction-note">
          <ArrowLeftRight size={13} /> We run both ways — Kanpur to Lucknow &amp; Lucknow to Kanpur
        </div>
        <p className="route-kanpur-note">Kanpur side: no station needed — we pick up &amp; drop at your doorstep.</p>
        <p>Estimated Delivery Time: <span>2 to 4 Hours</span></p>
      </div>
      <div className="pricing-panel">
        <h3>Simple & Transparent Pricing</h3>
        <table>
          <tbody>
            <tr><th>Weight Slab</th><th>Price</th></tr>
            {weightSlabs.map((slab) => (
              <tr key={slab.value}>
                <td>{slab.label}</td>
                <td>Rs {estimateTotalRupees(slab.slabFare)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <p className="route-pricing-note">Prices include base fare, platform fee &amp; GST. Door pickup available for an extra fee.</p>
      </div>
      <div className="box-stack">
        <div className="parcel-box">RIDERON</div>
        <h3>No Hidden Charges</h3>
        <p>What you see is what you pay.</p>
      </div>
    </section>
  );
}

function HomeStats() {
  return (
    <section className="container home-stats">
      <div><PackageCheck /><strong>5000+</strong><span>Parcels Delivered</span></div>
      <div><Target /><strong>98%</strong><span>On-Time Delivery</span></div>
      <div><CheckCircle2 /><strong>100%</strong><span>Customer Satisfaction</span></div>
      <div><Headphones /><strong>24/7</strong><span>Support Available</span></div>
    </section>
  );
}

function Testimonials() {
  return (
    <section className="container testimonials">
      <h2 className="center-title">What Our <span>Customers Say</span></h2>
      <div className="testimonial-grid">
        {testimonials.map((item, index) => (
          <article className="testimonial-card" key={item.name}>
            <div className="avatar">{item.name.split(" ").map((part) => part[0]).join("")}</div>
            <div>
              <h3>{item.name}</h3>
              <div className="stars">{Array.from({ length: 5 }).map((_, star) => <Star key={star} size={13} fill="currentColor" />)}</div>
              <p>"{item.text}"</p>
            </div>
          </article>
        ))}
      </div>
      <div className="slider-dots"><b /><span /><span /></div>
    </section>
  );
}

export default function HomePage() {
  return (
    <>
      <Header />
      <main>
        <HomeHero />
        <BookingCard />
        <Benefits />
        <HowItWorks />
        <RoutePricing />
        <HomeStats />
        <Testimonials />
      </main>
      <Footer />
    </>
  );
}
