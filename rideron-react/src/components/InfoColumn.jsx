import React from "react";

export default function InfoColumn({ icon, title, children }) {
  return (
    <div className="info-column">
      <div className="info-heading">
        <div className="info-icon">{icon}</div>
        <h2>{title}</h2>
      </div>
      <div className="heading-line" />
      {children}
    </div>
  );
}
