/** "9876543210" -> "98XXXXX210" — never show someone else's full phone number on screen. */
export function maskPhone(phone: string): string {
  if (phone.length < 6) return phone;
  const start = phone.slice(0, 2);
  const end = phone.slice(-3);
  const middle = 'X'.repeat(phone.length - start.length - end.length);
  return `${start}${middle}${end}`;
}
