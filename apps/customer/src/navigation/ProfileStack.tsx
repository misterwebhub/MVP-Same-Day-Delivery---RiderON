import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { color } from '@rideron/design-tokens';
import { ProfileHome } from '../features/profile/screens/ProfileHome';
import { SavedContacts } from '../features/profile/screens/SavedContacts';
import { Notifications } from '../features/profile/screens/Notifications';
import { Language } from '../features/profile/screens/Language';
import { Legal } from '../features/profile/screens/Legal';
import { HomeButton } from '../components/HomeButton';
import type { ProfileStackParamList } from './types';

const Stack = createNativeStackNavigator<ProfileStackParamList>();

/** Nested inside the "Profile" tab, per docs/08's features/profile/screens list. */
export function ProfileStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: color.secondary },
        headerTintColor: color.textInverse,
        headerTitleStyle: { color: color.textInverse },
        headerRight: () => <HomeButton tintColor={color.textInverse} />,
      }}
    >
      <Stack.Screen name="ProfileHome" component={ProfileHome} options={{ title: 'Profile' }} />
      <Stack.Screen name="SavedContacts" component={SavedContacts} options={{ title: 'Saved Addresses' }} />
      <Stack.Screen name="Notifications" component={Notifications} options={{ title: 'Notifications' }} />
      <Stack.Screen name="Language" component={Language} options={{ title: 'Language' }} />
      <Stack.Screen name="Legal" component={Legal} options={{ title: 'Legal' }} />
    </Stack.Navigator>
  );
}
