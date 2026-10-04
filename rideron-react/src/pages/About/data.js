import {
  CircleDollarSign,
  Clock3,
  Headphones,
  MapPin,
  Package,
  ShieldCheck,
  TrainFront,
  Users
} from "lucide-react";

export const values = [
  "Customer First",
  "Integrity & Transparency",
  "Safety & Responsibility",
  "Speed & Reliability",
  "Continuous Improvement"
];

export const benefits = [
  {
    icon: TrainFront,
    title: "Train-Based Delivery",
    text: "We use express trains for faster and on-time delivery."
  },
  {
    icon: ShieldCheck,
    title: "Safe Handling",
    text: "Every parcel is carefully handled and 100% safe with us."
  },
  {
    icon: MapPin,
    title: "Live Tracking",
    text: "Track your parcel in real time from Kanpur to Lucknow."
  },
  {
    icon: CircleDollarSign,
    title: "Affordable Pricing",
    text: "Best rates with no hidden charges. Transparent and honest pricing."
  },
  {
    icon: Headphones,
    title: "Customer Support",
    text: "We are always here to help you via call or WhatsApp."
  }
];

export const stats = [
  { icon: TrainFront, value: "2", title: "Stations Connected", detail: "Kanpur Central to Lucknow Charbagh" },
  { icon: Package, value: "5000+", title: "Parcels Delivered", detail: "And counting every day" },
  { icon: Users, value: "2500+", title: "Happy Customers", detail: "Trust built on service" },
  { icon: Clock3, value: "2-4", title: "Hours Delivery", detail: "Same-day delivery via express trains" }
];
