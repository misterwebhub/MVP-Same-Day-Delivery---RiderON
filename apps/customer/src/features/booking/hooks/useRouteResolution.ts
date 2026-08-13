import { useEffect, useState } from 'react';
import type { RouteSummary, Station } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { apiClient } from '../../../services/httpClient';

/** Resolves a RouteSummary from an origin/destination station pair — shared between
 * Home's quick-pick card and RouteSelect so both surfaces show the exact same
 * price/distance/cutoff preview the instant both stations are chosen. */
export function useRouteResolution(originStation: Station | null, destinationStation: Station | null) {
  const [route, setRoute] = useState<RouteSummary | null>(null);
  const [resolving, setResolving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setRoute(null);
    setError(null);
    if (!originStation || !destinationStation) return;
    if (originStation.id === destinationStation.id) {
      setError('Pickup and drop-off stations must be different.');
      return;
    }
    let cancelled = false;
    setResolving(true);
    apiClient.catalog
      .findRoute({ origin_station_id: originStation.id, destination_station_id: destinationStation.id })
      .then((foundRoute) => {
        if (!cancelled) setRoute(foundRoute);
      })
      .catch((e) => {
        if (cancelled) return;
        if (e instanceof ApiClientError && e.status === 404) {
          setError('No direct route runs between these stations yet.');
        } else {
          setError(e instanceof ApiClientError ? e.message : 'Could not load routes.');
        }
      })
      .finally(() => {
        if (!cancelled) setResolving(false);
      });
    return () => {
      cancelled = true;
    };
  }, [originStation, destinationStation]);

  return { route, resolving, error };
}
