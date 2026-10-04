import { useEffect, useState } from 'react';
import type { City, Station } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';

export interface StationOption extends Station {
  city: City;
}

/** Flat, single-tap station list — no city intermediate step. All stations across
 * every served city are loaded once and shown directly, per the "no multi-level
 * picker, finish booking fast" requirement. Shared by Home's quick-pick and the
 * booking flow's RouteSelect so station data is fetched once per app session. */
export function useAllStations() {
  const [stations, setStations] = useState<StationOption[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    apiClient.catalog
      .listCities()
      .then(async (cities) => {
        const perCity = await Promise.all(
          cities.map((city) => apiClient.catalog.listStations(city.id).then((list) => list.map((s) => ({ ...s, city })))),
        );
        if (!cancelled) setStations(perCity.flat());
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  return { stations, loading };
}
