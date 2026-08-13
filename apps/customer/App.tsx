import React, { useCallback, useEffect, useState } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import * as SplashScreen from 'expo-splash-screen';
import { useFonts, Poppins_500Medium, Poppins_600SemiBold } from '@expo-google-fonts/poppins';
import { Inter_400Regular, Inter_500Medium, Inter_600SemiBold } from '@expo-google-fonts/inter';
import { AuthProvider } from './src/hooks/useAuth';
import { BookingDraftProvider } from './src/features/booking/BookingDraftContext';
import { RootNavigator } from './src/navigation/RootNavigator';

SplashScreen.preventAutoHideAsync().catch(() => {
  /* no-op — only fails if already hidden */
});

export default function App() {
  const [fontsLoaded, fontsError] = useFonts({
    Poppins_600SemiBold,
    Poppins_500Medium,
    Inter_400Regular,
    Inter_600SemiBold,
    Inter_500Medium,
  });

  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (fontsLoaded || fontsError) {
      setReady(true);
    }
  }, [fontsLoaded, fontsError]);

  const onLayoutRootView = useCallback(async () => {
    if (ready) {
      await SplashScreen.hideAsync();
    }
  }, [ready]);

  if (!ready) {
    return null;
  }

  return (
    <SafeAreaProvider onLayout={onLayoutRootView}>
      <AuthProvider>
        <BookingDraftProvider>
          <RootNavigator />
        </BookingDraftProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
