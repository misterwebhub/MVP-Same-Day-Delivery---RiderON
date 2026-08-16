import React from 'react';
import { Platform, StyleSheet } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { color } from '@rideron/design-tokens';
import { Rides } from '../features/rides/screens/Rides';
import { Profile } from '../features/profile/screens/Profile';
import { Icon } from '../components/Icon';
import type { AppTabsParamList } from './types';

const Tab = createBottomTabNavigator<AppTabsParamList>();

/** Rides, Profile — simplified two-tab partner shell (was Dashboard/Earnings/Profile).
 * Earnings is no longer a primary nav destination per the UX simplification request,
 * but its screen/route and the backend endpoint are untouched — nothing was deleted,
 * just unlinked from the tab bar. Rides replaces Dashboard and contains the
 * Unassigned Rides / My Rides split (see features/rides/screens/Rides.tsx).
 * Tab icons + a taller, shadowed bar bring this in line with apps/customer's
 * tab bar per the "make the rider app UX attractive" request. */
export function AppTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: color.primary,
        tabBarInactiveTintColor: color.textSecondary,
        tabBarStyle: styles.tabBar,
        tabBarLabelStyle: styles.tabLabel,
      }}
    >
      <Tab.Screen
        name="Rides"
        component={Rides}
        options={{ tabBarIcon: ({ color: c, focused }) => <Icon name={focused ? 'bicycle' : 'bicycle-outline'} color={c} /> }}
      />
      <Tab.Screen
        name="Profile"
        component={Profile}
        options={{ tabBarIcon: ({ color: c, focused }) => <Icon name={focused ? 'person' : 'person-outline'} color={c} /> }}
      />
    </Tab.Navigator>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    backgroundColor: color.surface,
    borderTopColor: color.border,
    height: Platform.select({ ios: 84, default: 64 }),
    paddingTop: 8,
    shadowColor: color.shadow,
    shadowOpacity: 0.08,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: -2 },
    elevation: 8,
  },
  tabLabel: {
    fontFamily: 'Inter_500Medium',
    fontSize: 11,
  },
});
