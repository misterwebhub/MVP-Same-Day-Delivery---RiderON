import React from "react";

export default function SectionLabel({ children }) {
  return (
    <div className="section-label">
      <span>{children}</span>
      <i />
    </div>
  );
}
