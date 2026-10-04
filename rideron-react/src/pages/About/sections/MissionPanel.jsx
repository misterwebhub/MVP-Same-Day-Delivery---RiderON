import React from "react";
import { Check, Eye, Gem, Target } from "lucide-react";
import InfoColumn from "../../../components/InfoColumn";
import { values } from "../data";

export default function MissionPanel() {
  return (
    <section className="container mission-panel">
      <InfoColumn icon={<Target />} title="Our Mission">
        <p>
          To provide fast, safe and affordable station-to-station parcel delivery
          services, starting with Kanpur to Lucknow, and expanding to every major city in India.
        </p>
      </InfoColumn>

      <InfoColumn icon={<Eye />} title="Our Vision">
        <p>
          To become India&apos;s most trusted rail-based delivery network that connects
          every city and brings people closer, one parcel at a time.
        </p>
      </InfoColumn>

      <InfoColumn icon={<Gem />} title="Our Values">
        <ul className="values-list">
          {values.map((value) => (
            <li key={value}>
              <Check size={15} /> {value}
            </li>
          ))}
        </ul>
      </InfoColumn>
    </section>
  );
}
