import React from "react";

export default function Feature({ icon, title, text }) {
  return (
    <div className="feature">
      <div className="feature-icon">{icon}</div>
      <div>
        <strong>{title}</strong>
        <p>{text}</p>
      </div>
    </div>
  );
}
