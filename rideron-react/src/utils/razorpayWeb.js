/**
 * Razorpay's hosted checkout.js integration for the web app — same options
 * shape as react-native-razorpay's native SDK (mirrored from
 * apps/customer/src/utils/razorpayWeb.ts) so the calling code reads the same
 * way. Loaded lazily and cached so a second payment attempt doesn't re-fetch
 * the script.
 */

let scriptPromise = null;

function loadCheckoutScript() {
  if (typeof window === "undefined") {
    return Promise.reject(new Error("Razorpay checkout is only available in a browser."));
  }
  if (window.Razorpay) {
    return Promise.resolve();
  }
  if (!scriptPromise) {
    scriptPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = "https://checkout.razorpay.com/v1/checkout.js";
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => {
        scriptPromise = null;
        reject(new Error("Could not load the Razorpay checkout script."));
      };
      document.body.appendChild(script);
    });
  }
  return scriptPromise;
}

/** Opens Razorpay's web Checkout overlay, resolving with the genuine
 * razorpay_order_id/razorpay_payment_id/razorpay_signature Razorpay hands
 * back on success, or rejecting (including when the user just closes the
 * overlay without paying). */
export async function openRazorpayCheckoutWeb(options) {
  await loadCheckoutScript();
  return new Promise((resolve, reject) => {
    const RazorpayCtor = window.Razorpay;
    if (!RazorpayCtor) {
      reject(new Error("Razorpay checkout script did not load correctly."));
      return;
    }
    const instance = new RazorpayCtor({
      ...options,
      handler: (response) => resolve(response),
      modal: {
        ondismiss: () => reject({ description: "Payment cancelled." }),
      },
    });
    instance.open();
  });
}
