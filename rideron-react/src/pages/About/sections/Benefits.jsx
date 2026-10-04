import React from "react";
import SectionLabel from "../../../components/SectionLabel";
import { benefits } from "../data";

export default function Benefits() {
  return (
    <section className="benefits container">
      <div className="center-heading">
        <SectionLabel>WHY CHOOSE RIDERON</SectionLabel>
        <h2>We Go the Extra Mile</h2>
      </div>

      <div className="benefit-grid">
        {benefits.map(({ icon: Icon, title, text }) => (
          <article className="benefit-card" key={title}>
            <Icon className="benefit-icon" />
            <h3>{title}</h3>
            <p>{text}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
