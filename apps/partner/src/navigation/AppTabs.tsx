import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { color } from '@rideron/design-tokens';
import { Rides } from '../features/rides/screens/Rides';
import { Profile } from '../features/profile/screens/Profile';
import type { AppTabsParamList } from './types';

const Tab = createBottomTabNavigator<AppTabsParamList>();

/** Rides, Profile — simplified two-tab partner shell (was Dashboard/Earnings/Profile).
 * Earnings is no longer a primary nav destination per the UX simplification request,
 * but its screen/route and the backend endpoint are untouched — nothing was deleted,
 * just unlinked from the tab bar. Rides replaces Dashboard and contains the
 * Unassigned Rides / My Rides split (see features/rides/screens/Rides.tsx). */
export function AppTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: color.primary,
        tabBarInactiveTintColor: color.textSecondary,
        tabBarStyle: { backgroundColor: color.surface, borderTopColor: color.border },
      }}
    >
      <Tab.Screen name="Rides" component={Rides} />
      <Tab.Screen name="Profile" component={Profile} />
    </Tab.Navigator>
  );
}
