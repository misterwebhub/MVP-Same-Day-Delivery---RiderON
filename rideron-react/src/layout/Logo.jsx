import React from "react";

export default function Logo({ onClick }) {
  return (
    <a href="#home" className="logo" aria-label="RiderON home" onClick={onClick}>
      <img src="/images/Logo-white.png" alt="RiderON - Fast and same day delivery" />  
    </a>
  );
}
