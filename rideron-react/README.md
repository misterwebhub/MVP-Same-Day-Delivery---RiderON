# RiderON React About Page

A responsive React + Vite implementation inspired by the supplied RiderON About Us screenshot.

## Run

```bash
npm install
npm run dev
```

Then open the local Vite URL shown in the terminal.

## Replace the placeholder visuals

The implementation intentionally uses CSS placeholders for the RiderON rider/train imagery because the supplied screenshot is a reference design rather than separate source assets.

Recommended assets:
- `public/images/rider-delivery.jpg`
- `public/images/train-route.jpg`
- `public/images/rideron-logo.svg`

Then update the `.rider-image` and `.train-image` styles in `src/styles.css`.

## Suggested next step

Add React Router and create:
- `/`
- `/book-parcel`
- `/track-parcel`
- `/pricing`
- `/about`
- `/contact`
