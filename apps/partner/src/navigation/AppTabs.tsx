import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { color } from '@rideron/design-tokens';
import { Dashboard } from '../features/dashboard/screens/Dashboard';
import { Earnings } from '../features/earnings/screens/Earnings';
import { Profile } from '../features/profile/screens/Profile';
import type { AppTabsParamList } from './types';

const Tab = createBottomTabNavigator<AppTabsParamList>();

/** Dashboard, Earnings, Profile — the three-tab partner shell per docs/06's Screens & data section. */
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
      <Tab.Screen name="Dashboard" component={Dashboard} />
      <Tab.Screen name="Earnings" component={Earnings} />
      <Tab.Screen name="Profile" component={Profile} />
    </Tab.Navigator>
  );
}
