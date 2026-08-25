import { manipulateAsync, SaveFormat } from 'expo-image-manipulator';

/** Backend's parcel-photo upload limit is `max:5120` KB (see
 * backend/app/Http/Controllers/Api/V1/ParcelPhotoController.php) — 5 MB. Target
 * comfortably under that so JPEG/base64 rounding never tips it over the line. */
const MAX_UPLOAD_BYTES = 4.5 * 1024 * 1024;

/** Progressively smaller/lower-quality renders to try, largest first. Modern
 * phone cameras commonly produce 8-12 MP (3000-4000px) originals several MB in
 * size — the picker's `quality: 0.6` capture option alone doesn't reliably get
 * under the limit since it only affects JPEG quality, not pixel dimensions. */
const ATTEMPTS: Array<{ width: number; quality: number }> = [
  { width: 1600, quality: 0.7 },
  { width: 1280, quality: 0.6 },
  { width: 1024, quality: 0.5 },
  { width: 800, quality: 0.4 },
  { width: 640, quality: 0.35 },
];

function base64ByteSize(base64: string): number {
  const padding = base64.endsWith('==') ? 2 : base64.endsWith('=') ? 1 : 0;
  return (base64.length * 3) / 4 - padding;
}

/**
 * Resizes + JPEG-compresses a picked photo so it fits under the backend's 5 MB
 * upload limit, trying progressively smaller renders until one fits. Falls
 * back to the smallest/lowest-quality attempt if even that doesn't (better to
 * ship an attempt the server can still reject with a clear message than to
 * silently drop the photo).
 *
 * @param uri Local URI from expo-image-picker (`file://` on native, `blob:`/
 *   `data:` on web) — either works as manipulateAsync's input.
 * @param knownWidth The picked asset's own `.width` (0 if the system didn't
 *   report one). Used to cap resize targets so a photo already smaller than a
 *   given step is never *upscaled* (which would only grow the file).
 * @returns The compressed render's local URI — same URI shape as the input
 *   (`file://` on native, `data:`/`blob:` on web), which the existing upload
 *   path (httpClient.ts) already knows how to handle either way.
 */
export async function compressImageForUpload(uri: string, knownWidth?: number): Promise<string> {
  const widthCap = knownWidth && knownWidth > 0 ? knownWidth : Infinity;

  let lastUri = uri;
  for (const { width, quality } of ATTEMPTS) {
    const targetWidth = Math.min(width, widthCap);
    const result = await manipulateAsync(uri, [{ resize: { width: targetWidth } }], {
      compress: quality,
      format: SaveFormat.JPEG,
      base64: true,
    });
    lastUri = result.uri;
    if (result.base64 && base64ByteSize(result.base64) <= MAX_UPLOAD_BYTES) {
      return result.uri;
    }
  }
  return lastUri;
}
