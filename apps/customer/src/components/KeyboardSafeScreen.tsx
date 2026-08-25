import React from 'react';
import { KeyboardAvoidingView, Platform, StyleSheet, ViewStyle } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

type Props = {
  children: React.ReactNode;
  style?: ViewStyle | ViewStyle[];
};

/**
 * Fixes a mobile bug reported on every form screen: focusing a TextInput let
 * the on-screen keyboard cover the field/footer instead of the content
 * shifting up. Android's windowSoftInputMode is "adjustResize"
 * (AndroidManifest.xml) but edge-to-edge display (default since Expo
 * 52+/RN new-arch) means the window no longer resizes around the keyboard
 * on its own — an explicit KeyboardAvoidingView is required.
 *
 * Use in place of a screen's root View. Pair with `useSafeBottomPadding()`
 * on any bottom-fixed footer/CTA so it also clears gesture/3-button nav
 * bars (a separate reported bug — see that hook's docblock).
 */
export function KeyboardSafeScreen({ children, style }: Props) {
  const insets = useSafeAreaInsets();

  return (
    <KeyboardAvoidingView
      style={[styles.flex, style]}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={Platform.OS === 'ios' ? insets.top : 0}
    >
      {children}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
});
