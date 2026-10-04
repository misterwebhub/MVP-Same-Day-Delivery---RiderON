/** Backend amounts are always integer paise — never format raw paise directly in a screen. */
export function formatPaise(amountPaise) {
  const rupees = amountPaise / 100;
  const rounded = Number.isInteger(rupees) ? rupees.toString() : rupees.toFixed(2);
  return `₹${rounded.replace(/\B(?=(\d{3})+(?!\d)(?=\.|$))/g, ",")}`;
}
