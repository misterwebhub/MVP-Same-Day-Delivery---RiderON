import React from "react";
import { MapPin } from "lucide-react";
import SectionLabel from "../../../components/SectionLabel";

export default function Story() {
  return (
    <section className="container story">
      <div className="story-visual image-placeholder train-image">
        <div className="route">
          <span className="station kanpur">
            <MapPin /> Kanpur Central
          </span>
          <div className="dotted-route" />
          <span className="station lucknow">
            <MapPin /> Lucknow Charbagh
          </span>
        </div>
      </div>

      <div className="story-content">
        <SectionLabel>OUR STORY</SectionLabel>
        <h2>How It All Started</h2>
        <p>
          We noticed a common problem while sending parcels between Kanpur and
          Lucknow - it was either too slow, too expensive, or simply not reliable.
        </p>
        <p>
          That&apos;s when we thought - why not use the speed and reliability of Indian
          Railways to deliver parcels faster?
        </p>
        <p>
          RiderON was born with a simple idea - make parcel delivery as fast and
          reliable as train travel.
        </p>
        <p>
          Today, we are proud to be the first choice for hundreds of customers
          between Kanpur and Lucknow.
        </p>
      </div>
    </section>
  );
}
