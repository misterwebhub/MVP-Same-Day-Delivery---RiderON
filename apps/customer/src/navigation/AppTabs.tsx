import React from 'react';
import { Platform, StyleSheet, View } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { color, radius } from '@rideron/design-tokens';
import { Home } from '../features/home/screens/Home';
import { OrderList } from '../features/orders/screens/OrderList';
import { SupportStack } from './SupportStack';
import { ProfileStack } from './ProfileStack';
import { Icon } from '../components/Icon';
import type { AppTabsParamList } from './types';

const Tab = createBottomTabNavigator<AppTabsParamList>();

/** Placeholder — BookParcel's tabBarButton is overridden below to push the root Booking stack instead of rendering this. */
function BookParcelPlaceholder() {
  return null;
}

/**
 * Home, Orders, BookParcel (center CTA), Support, Profile — mirrors the
 * reference screenshot's tab bar per docs/01's navigation architecture
 * section. BookParcel intercepts its own tab press so it never becomes the
 * "active" tab; it just pushes the root-level BookingStack on top.
 */
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
        name="Home"
        component={Home}
        options={{ tabBarIcon: ({ color: c, focused }) => <Icon name={focused ? 'home' : 'home-outline'} color={c} /> }}
      />
      <Tab.Screen
        name="Orders"
        component={OrderList}
        options={{
          tabBarLabel: 'Bookings',
          tabBarIcon: ({ color: c, focused }) => <Icon name={focused ? 'file-tray-full' : 'file-tray-full-outline'} color={c} />,
        }}
      />
      <Tab.Screen
        name="BookParcel"
        component={BookParcelPlaceholder}
        options={{
          tabBarLabel: 'Send',
          tabBarIcon: () => (
            <View style={styles.ctaButton}>
              <Icon name="cube" size={24} color={color.textInverse} />
            </View>
          ),
        }}
        listeners={({ navigation }) => ({
          tabPress: (e) => {
            e.preventDefault();
            // Root-level navigate — BookingStack is a sibling of Tabs in RootNavigator,
            // not a child of AppTabs, so it covers the tab bar when pushed.
            navigation.getParent()?.navigate('Booking');
          },
        })}
      />
      <Tab.Screen
        name="Support"
        component={SupportStack}
        options={{
          tabBarLabel: 'Support',
          tabBarIcon: ({ color: c, focused }) => <Icon name={focused ? 'headset' : 'headset-outline'} color={c} />,
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileStack}
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
  },
  tabLabel: {
    fontFamily: 'Inter_500Medium',
    fontSize: 11,
  },
  ctaButton: {
    width: 52,
    height: 52,
    borderRadius: radius.pill,
    backgroundColor: color.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: -28,
    shadowColor: color.shadow,
    shadowOpacity: 0.25,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 4 },
    elevation: 6,
  },
});
