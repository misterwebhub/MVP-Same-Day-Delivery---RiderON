# Remote base-URL config

`rideron.php` is a single standalone PHP file — **not** part of the Laravel
app in `backend/`, and has no dependencies. It just answers "which backend
host should the app use right now?" so that host can change without shipping
a new app build.

## Deploy

1. Upload `rideron.php` as-is to the web root of `impixoexports.com`, so it's
   reachable at `https://impixoexports.com/rideron.php`.
2. Point the two subdomains at separate deployments of the Laravel backend
   (`backend/`) — a production instance and a development/staging instance,
   each with its own `.env` (database, `APP_ENV`, `SMS_DRIVER`, etc.):
   - `https://rideron.impixoexports.com` → production
   - `https://devrideron.impixoexports.com` → development
3. Both subdomains need valid HTTPS (Expo/React Native's fetch will refuse a
   plain HTTP request to a non-localhost host on modern OS versions).

## How the apps use it

`apps/customer/src/services/httpClient.ts` and
`apps/partner/src/services/httpClient.ts` call:

```
GET https://impixoexports.com/rideron.php?env=development   (Expo Go / dev builds, __DEV__ === true)
GET https://impixoexports.com/rideron.php?env=production    (release builds, __DEV__ === false)
```

Response shape:

```json
{ "environment": "development", "base_url": "https://devrideron.impixoexports.com/api/v1" }
```

Resolution order in the apps (first match wins):

1. `EXPO_PUBLIC_API_URL` from an untracked `.env` file — still the right
   knob for pointing at a local LAN IP during same-network development
   instead of the hosted dev backend.
2. The value returned by `rideron.php` for the current environment (fetched
   once per app session, cached on-device via AsyncStorage).
3. The last value successfully cached from a previous session, if the
   network call above fails (offline, DNS, host down).
4. A hardcoded fallback baked into the app (same two URLs as in
   `rideron.php`), so the app is never left with no target at all.

## Repointing an environment later

Edit the two URLs inside `rideron.php` and re-upload — no app rebuild
needed. Already-installed apps pick up the change the next time they
successfully reach `impixoexports.com` (and cache it for the next offline
launch).
