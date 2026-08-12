/**
 * Generates a client-side idempotency key for the `Idempotency-Key` header
 * required by the backend's `idempotent` middleware on POST /orders,
 * POST /orders/{order}/cancel and POST /payments/{payment}/verify.
 *
 * Uses crypto.randomUUID() where available (Hermes/modern JSC + Node 19+);
 * falls back to a Math.random-based v4-shaped string otherwise so this
 * still works in older RN JS engines without pulling in a uuid dependency.
 */
export function generateIdempotencyKey(): string {
  const g = globalThis as { crypto?: { randomUUID?: () => string } };
  if (g.crypto?.randomUUID) {
    return g.crypto.randomUUID();
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}
