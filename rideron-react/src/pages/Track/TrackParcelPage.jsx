import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Search } from "lucide-react";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { apiClient } from "../../lib/apiClient";
import { formatDateLabel } from "../../utils/date";

// There is no public/anonymous order-tracking endpoint on the backend (see
// routes/api.php — GET /orders and GET /orders/{id} both sit behind
// auth:sanctum). So "Track Parcel" here is an authenticated search/filter UI
// over the signed-in user's own orders.list() results, not a public
// reference-number lookup — matching what ProtectedRoute below enforces.
export default function TrackParcelPage() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [query, setQuery] = useState("");

  useEffect(() => {
    apiClient.orders
      .list({ per_page: 50 })
      .then((result) => setOrders(result.items))
      .catch(() => setError("Could not load your orders."))
      .finally(() => setLoading(false));
  }, []);

  const q = query.trim().toUpperCase();
  const filtered = orders.filter((order) => {
    if (!q) return true;
    const route = `${order.route?.origin_station?.name ?? ""} ${order.route?.destination_station?.name ?? ""}`.toUpperCase();
    return order.booking_reference.toUpperCase().includes(q) || route.includes(q);
  });

  return (
    <>
      <Header />
      <main className="account-page">
        <div className="container">
          <h1>Track Parcel</h1>
          <p className="step-subtitle">Search your bookings by order ID or route.</p>

          <div className="search-with-icon">
            <Search size={16} />
            <input
              type="text"
              placeholder="Order ID or route (e.g. Kanpur, Lucknow)"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
            />
          </div>

          {loading ? <p className="step-note">Loading…</p> : null}
          {error ? <p className="form-error">{error}</p> : null}
          {!loading && !error && filtered.length === 0 ? <p className="step-note">No matching orders found.</p> : null}

          <div className="orders-list">
            {filtered.map((order) => (
              <Link to={`/orders/${order.id}`} key={order.id} className="order-row">
                <div className="order-row-main">
                  <strong>{order.booking_reference}</strong>
                  <span>
                    {order.route?.origin_station?.name ?? "—"} → {order.route?.destination_station?.name ?? "—"}
                  </span>
                  <small>{order.booking_date ? formatDateLabel(order.booking_date) : "—"}</small>
                  <em>{order.status.replace(/_/g, " ")}</em>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </main>
      <Footer />
    </>
  );
}
