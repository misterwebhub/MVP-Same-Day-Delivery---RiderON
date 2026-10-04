import React from "react";
import { ArrowRight, MessageCircle, User } from "lucide-react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import Logo from "./Logo";
import { navItems } from "../data/navigation";
import { useAuth } from "../context/AuthContext";

export default function Header() {
  const location = useLocation();
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();

  const isActive = (item) => !item.hash && location.pathname === item.path;

  return (
    <header className="site-header">
      <div className="container nav-wrap">
        <Logo onClick={(event) => { event.preventDefault(); navigate("/"); }} />
        <nav className="desktop-nav">
          {navItems.map((item) => {
            const target = item.hash ? `${item.path}#${item.hash}` : item.path;
            return (
              <Link key={item.label} className={isActive(item) ? "active" : ""} to={target}>
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="header-actions">
          <div className="phone-chip">
            <MessageCircle size={25} />
            <span>
              <strong>+91 700 123 4567</strong>
              <small>Mon - Sat, 8AM - 8PM</small>
            </span>
          </div>
          {isAuthenticated ? (
            <Link to="/orders" className="account-chip">
              <User size={17} /> My Account
            </Link>
          ) : (
            <Link to="/login" className="account-chip">
              <User size={17} /> Login
            </Link>
          )}
          <Link className="nav-cta" to="/book">
            Book Now <ArrowRight size={17} />
          </Link>
        </div>
      </div>
    </header>
  );
}
