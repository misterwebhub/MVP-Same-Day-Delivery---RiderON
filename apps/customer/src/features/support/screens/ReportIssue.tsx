import React from 'react';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { ScreenStub } from '../../../components/ScreenStub';
import type { SupportStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<SupportStackParamList, 'ReportIssue'>;

export function ReportIssue({ route }: Props) {
  return <ScreenStub title="Report an Issue" note={route.params.orderId ? `Order #${route.params.orderId}` : undefined} />;
}
