/** Local YYYY-MM-DD — never toISOString(), which shifts to UTC and can land on the wrong day. */
export function toYMD(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function todayYMD(): string {
  return toYMD(new Date());
}

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** "2026-08-12" -> "Wed, 12 Aug". Mirrors apps/customer/src/utils/date.ts's formatDateLabel
 * so the same order reads identically on both apps — manual formatting (not Intl) to avoid
 * relying on full-icu in Hermes. */
export function formatDateLabel(ymd: string): string {
  const [y, m, d] = ymd.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  return `${WEEKDAYS[date.getDay()]}, ${d} ${MONTHS[m - 1]}`;
}

/** "19:00:00" -> "19:00" — schedule times come back with seconds, trimmed for display. */
export function formatTime(hms: string): string {
  return hms.slice(0, 5);
}
