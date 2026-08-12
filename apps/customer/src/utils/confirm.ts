import { Alert, Platform } from 'react-native';

/**
 * Cross-platform confirm dialog. react-native-web's Alert.alert is a documented
 * no-op (`static alert() {}` in react-native-web/dist/exports/Alert), so a plain
 * Alert.alert() call silently does nothing on web — falls back to the browser's
 * native window.confirm there, and uses the real native Alert on iOS/Android.
 */
export function confirmAction(title: string, message: string, confirmLabel: string, onConfirm: () => void, destructive = false): void {
  if (Platform.OS === 'web') {
    // eslint-disable-next-line no-alert
    if (window.confirm(`${title}\n\n${message}`)) {
      onConfirm();
    }
    return;
  }
  Alert.alert(title, message, [
    { text: 'Cancel', style: 'cancel' },
    { text: confirmLabel, style: destructive ? 'destructive' : 'default', onPress: onConfirm },
  ]);
}
