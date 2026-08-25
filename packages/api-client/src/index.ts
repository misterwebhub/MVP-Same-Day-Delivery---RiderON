export * from './errors';
export * from './tokenStorage';
export * from './idempotency';
export { HttpClient } from './httpClient';
export type { ApiClientOptions, RequestOptions, FormDataFile, UploadFileParams } from './httpClient';
export { createResources } from './resources';
export type { ApiResources, GeoCoords } from './resources';

import { HttpClient, type ApiClientOptions } from './httpClient';
import { createResources, type ApiResources } from './resources';

export type ApiClient = ApiResources & { http: HttpClient };

/** Builds the full typed client — the only thing app code should normally import. */
export function createApiClient(options: ApiClientOptions): ApiClient {
  const http = new HttpClient(options);
  return { ...createResources(http), http };
}
