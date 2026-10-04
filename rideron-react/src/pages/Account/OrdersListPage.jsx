import React, { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { RefreshCw } from "lucide-react";
import Header from "../../layout/Header";
import Footer from "../../layout/Footer";
import { apiClient } from "../../lib/apiClient";
import { formatPaise } from "../../utils/currency";
import { formatDateLabel } from "../../utils/date";

const COMPLETED_STATUSES = ["COMPLETED", "DELIVERED"];
const CANCELLED_STATUSES = ["CANCELLED", "REFUND_PENDING", "REFUNDED", "FAILED_DELIVERY", "DISPUTED"];

function matchesTab(status, tab) {
  if (tab === "completed") return COMPLETED_STATUSES.includes(status);
  if (tab === "cancelled") return CANCELLED_STATUSES.includes(status);
  return !COMPLETED_STATUSES.includes(status) && !CANCELLED_STATUSES.includes(status);
}

const TABS = [
  { key: "active", label: "Active" },
  { key: "completed", label: "Completed" },
  { key: "cancelled", label: "Cancelled" },
];

export default function OrdersListPage() {
  const [orders, setOrders] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [tab, setTab] = useState("active");
  const [search, setSearch] = useState("");

  const load = useCallback(async () => {
    try {
      const result = await apiClient.orders.list({ per_page: 50 });
      setOrders(result.items);
      setError(null);
    } catch {
      setError("Could not load your orders.");
    }
  }, []);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

  const onRefresh = async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  };

  const query = search.trim().toUpperCase();
  const filtered = (orders ?? [])
    .filter((o) => matchesTab(o.status, tab))
    .filter((o) => query.length === 0 || o.booking_reference.toUpperCase().includes(query));

  return (
    <>
      <Header />
      <main className="account-page">
        <div className="container">
          <div className="account-page-header">
            <h1>My Bookings</h1>
            {/* Explicit refresh button — browser mouse/trackpad has no native
             * pull-to-refresh gesture, so this is the only reliable refresh
             * path on web, mirroring the fix already applied in apps/customer. */}
            <button type="button" className="refresh-btn" onClick={onRefresh} disabled={refreshing || loading}>
              <RefreshCw size={16} className={refreshing ? "spin" : ""} /> Refresh
            </button>
          </div>

          <input
            type="text"
            className="search-input"
            placeholder="Search by order ID"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />

          <div className="tab-row">
            {TABS.map((t) => (
              <button key={t.key} type="button" className={`tab-chip${tab === t.key ? " active" : ""}`} onClick={() => setTab(t.key)}>
                {t.label}
              </button>
            ))}
          </div>

          {loading ? <p className="step-note">Loading orders…</p> : null}

          {!loading && filtered.length === 0 ? (
            <p className="step-note">{error ?? (query ? `No orders match "${search.trim()}".` : `No ${tab} orders yet.`)}</p>
          ) : null}

          <div className="orders-list">
            {filtered.map((order) => (
              <Link to={`/orders/${order.id}`} key={order.id} className="order-row">
                <div className="order-row-main">
                  <strong>{order.booking_reference}</strong>
                  <span>
                    {order.pickup_address?.text ?? order.route?.origin_station?.name ?? "—"} →{" "}
                    {order.delivery_address?.text ?? order.route?.destination_station?.name ?? "—"}
                  </span>
                  <small>
                    {order.booking_date ? formatDateLabel(order.booking_date) : "—"}
                    {order.route_schedule ? ` • ${order.route_schedule.departure_time.slice(0, 5)}` : ""}
                  </small>
                  <em>{order.status.replace(/_/g, " ")}</em>
                </div>
                <div className="order-row-amount">{formatPaise(order.total_amount_paise)}</div>
              </Link>
            ))}
          </div>
        </div>
      </main>
      <Footer />
    </>
  );
}
