import type { RazorpayCheckoutOptions, RazorpaySuccessResult } from 'react-native-razorpay';

/**
 * react-native-razorpay is a native-only module (RN bridge) — it has no web
 * implementation, so on `Platform.OS === 'web'` (Expo web, and how this app
 * gets tested in a browser) we fall back to Razorpay's own hosted
 * checkout.js script instead. Same options shape as the native SDK, loaded
 * lazily and cached so a second payment attempt doesn't re-fetch the script.
 */

declare global {
  interface Window {
    Razorpay?: new (options: Record<string, unknown>) => { open: () => void };
  }
}

let scriptPromise: Promise<void> | null = null;

function loadCheckoutScript(): Promise<void> {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('Razorpay checkout is only available in a browser.'));
  }
  if (window.Razorpay) {
    return Promise.resolve();
  }
  if (!scriptPromise) {
    scriptPromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => {
        scriptPromise = null;
        reject(new Error('Could not load the Razorpay checkout script.'));
      };
      document.body.appendChild(script);
    });
  }
  return scriptPromise;
}

/** Opens Razorpay's web Checkout overlay, resolving/rejecting the same way
 * `RazorpayCheckout.open()` does on native, so callers don't need to branch
 * on platform beyond picking which of the two to call. */
export async function openRazorpayCheckoutWeb(options: RazorpayCheckoutOptions): Promise<RazorpaySuccessResult> {
  await loadCheckoutScript();
  return new Promise((resolve, reject) => {
    const RazorpayCtor = window.Razorpay;
    if (!RazorpayCtor) {
      reject(new Error('Razorpay checkout script did not load correctly.'));
      return;
    }
    const instance = new RazorpayCtor({
      ...options,
      handler: (response: RazorpaySuccessResult) => resolve(response),
      modal: {
        // User closed the overlay without paying — reject like the native
        // SDK's PAYMENT_ERROR event does, so Payment.tsx's catch path is shared.
        ondismiss: () => reject({ description: 'Payment cancelled.' }),
      },
    });
    instance.open();
  });
}
