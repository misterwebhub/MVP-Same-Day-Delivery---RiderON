import React from 'react';
import { Ionicons } from '@expo/vector-icons';

export type IconName = React.ComponentProps<typeof Ionicons>['name'];

interface IconProps {
  name: IconName;
  size?: number;
  color: string;
}

/** Thin typed wrapper around Ionicons, mirrors apps/customer/src/components/Icon.tsx
 * so both apps import icons the same way. */
export function Icon({ name, size = 22, color }: IconProps) {
  return <Ionicons name={name} size={size} color={color} />;
}
