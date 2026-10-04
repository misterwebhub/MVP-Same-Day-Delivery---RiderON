import React from "react";
import { stats } from "../data";

export default function Stats() {
  return (
    <section className="container stats-panel">
      {stats.map(({ icon: Icon, value, title, detail }) => (
        <div className="stat" key={title}>
          <div className="stat-icon">
            <Icon />
          </div>
          <div>
            <strong>{value}</strong>
            <h3>{title}</h3>
            <p>{detail}</p>
          </div>
        </div>
      ))}
    </section>
  );
}
