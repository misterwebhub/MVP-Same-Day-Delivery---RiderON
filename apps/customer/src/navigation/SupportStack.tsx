import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { color } from '@rideron/design-tokens';
import { SupportHome } from '../features/support/screens/SupportHome';
import { ReportIssue } from '../features/support/screens/ReportIssue';
import { TicketDetail } from '../features/support/screens/TicketDetail';
import { Faq } from '../features/support/screens/Faq';
import type { SupportStackParamList } from './types';

const Stack = createNativeStackNavigator<SupportStackParamList>();

/** Nested inside the "Support" tab so pushes stay under the tab bar, per docs/08's features/support/screens list. */
export function SupportStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: color.secondary },
        headerTintColor: color.textInverse,
        headerTitleStyle: { color: color.textInverse },
      }}
    >
      <Stack.Screen name="SupportHome" component={SupportHome} options={{ title: 'Support' }} />
      <Stack.Screen name="ReportIssue" component={ReportIssue} options={{ title: 'Report an Issue' }} />
      <Stack.Screen name="TicketDetail" component={TicketDetail} options={{ title: 'Ticket' }} />
      <Stack.Screen name="Faq" component={Faq} options={{ title: 'FAQ' }} />
    </Stack.Navigator>
  );
}
