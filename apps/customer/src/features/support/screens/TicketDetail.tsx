import React from 'react';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { ScreenStub } from '../../../components/ScreenStub';
import type { SupportStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<SupportStackParamList, 'TicketDetail'>;

export function TicketDetail({ route }: Props) {
  return <ScreenStub title="Ticket Detail" note={`Ticket #${route.params.ticketId}`} />;
}
