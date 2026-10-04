import React from "react";
import { Mail, MapPin, MessageCircle, Phone, TrainFront } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";
import Logo from "./Logo";
import { navItems } from "../data/navigation";

function FooterLinks({ title, links }) {
  return (
    <div className="footer-links">
      <h3>{title}</h3>
      {links.map((link) => (
        <Link to={link.hash ? `${link.path}#${link.hash}` : link.path} key={link.label}>
          {link.label}
        </Link>
      ))}
    </div>
  );
}

export default function Footer() {
  const navigate = useNavigate();
  return (
    <footer className="footer" id="contact-us">
      <div className="footer-cta">
        <div className="container footer-cta-inner">
          <div>
            <h2>Need to send a parcel today?</h2>
            <p>Book now and experience fast, safe & same-day delivery.</p>
            <Link to="/book" className="primary-btn">
              Book Your Parcel Now
            </Link>
          </div>
          <div className="footer-cta-icon" aria-hidden="true">
            <TrainFront size={46} />
          </div>
        </div>
      </div>
      <div className="container footer-grid">
        <div className="footer-brand">
          <Logo onClick={(event) => { event.preventDefault(); navigate("/"); }} />
          <p>Fast, safe & affordable station-to-station parcel delivery between Kanpur Central and Lucknow Charbagh.</p>
          <div className="socials">
            <span>wa</span>
            <span>ig</span>
            <span>fb</span>
          </div>
        </div>

        <FooterLinks title="Quick Links" links={navItems} />
        <FooterLinks
          title="Support"
          links={[
            { label: "Contact Us", path: "/contact" },
            { label: "Track Parcel", path: "/track" },
            { label: "FAQ", path: "/services" },
            { label: "Terms & Conditions", path: "/services" }
          ]}
        />

        <div className="footer-contact">
          <h3>Contact Us</h3>
          <p><Phone size={13} /> +91 700 123 4567</p>
          <p><Mail size={13} /> support@rideron.in</p>
          <p><MessageCircle size={13} /> Mon - Sat, 8AM - 8PM</p>
          <p><MapPin size={13} /> Kanpur, Uttar Pradesh</p>
        </div>
      </div>
      <div className="copyright">Copyright 2024 RiderON. All Rights Reserved.</div>
    </footer>
  );
}
