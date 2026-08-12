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
