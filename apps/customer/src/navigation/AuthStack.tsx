import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Splash } from '../features/auth/screens/Splash';
import { Onboarding } from '../features/auth/screens/Onboarding';
import { Login } from '../features/auth/screens/Login';
import { OtpVerify } from '../features/auth/screens/OtpVerify';
import { ProfileSetup } from '../features/auth/screens/ProfileSetup';
import type { AuthStackParamList } from './types';

const Stack = createNativeStackNavigator<AuthStackParamList>();

/** Splash -> Onboarding -> Login -> OTPVerify -> ProfileSetup, per docs/01's navigation architecture section. */
export function AuthStack() {
  return (
    <Stack.Navigator initialRouteName="Splash" screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Splash" component={Splash} />
      <Stack.Screen name="Onboarding" component={Onboarding} />
      <Stack.Screen name="Login" component={Login} />
      <Stack.Screen name="OtpVerify" component={OtpVerify} />
      <Stack.Screen name="ProfileSetup" component={ProfileSetup} />
    </Stack.Navigator>
  );
}
