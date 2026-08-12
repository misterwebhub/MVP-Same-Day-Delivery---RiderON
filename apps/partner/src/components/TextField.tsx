import React from 'react';
import { StyleSheet, Text, TextInput, View, type KeyboardTypeOptions } from 'react-native';
import { color, radius, space, typography, MIN_TOUCH_TARGET } from '@rideron/design-tokens';

interface TextFieldProps {
  label?: string;
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
  keyboardType?: KeyboardTypeOptions;
  maxLength?: number;
  autoFocus?: boolean;
  error?: string | null;
  prefix?: string;
  multiline?: boolean;
  numberOfLines?: number;
  secureTextEntry?: boolean;
}

/** Generic reusable labeled text input — mirrors apps/customer/src/components/TextField.tsx (adds secureTextEntry for the partner password field). */
export function TextField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
  maxLength,
  autoFocus,
  error,
  prefix,
  multiline,
  numberOfLines,
  secureTextEntry,
}: TextFieldProps) {
  return (
    <View style={styles.container}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={[styles.inputRow, multiline ? styles.inputRowMultiline : null, error ? styles.inputRowError : null]}>
        {prefix ? <Text style={styles.prefix}>{prefix}</Text> : null}
        <TextInput
          style={[styles.input, multiline ? styles.inputMultiline : null]}
          value={value}
          onChangeText={onChangeText}
          placeholder={placeholder}
          placeholderTextColor={color.textSecondary}
          keyboardType={keyboardType}
          maxLength={maxLength}
          autoFocus={autoFocus}
          autoCapitalize="none"
          autoCorrect={false}
          multiline={multiline}
          numberOfLines={numberOfLines}
          secureTextEntry={secureTextEntry}
          textAlignVertical={multiline ? 'top' : 'center'}
        />
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
  label: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[1],
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: MIN_TOUCH_TARGET,
    borderWidth: 1,
    borderColor: color.border,
    borderRadius: radius.md,
    backgroundColor: color.surface,
    paddingHorizontal: space[4],
  },
  inputRowError: {
    borderColor: color.error,
  },
  inputRowMultiline: {
    minHeight: 96,
    alignItems: 'flex-start',
    paddingVertical: space[3],
  },
  inputMultiline: {
    minHeight: 72,
  },
  prefix: {
    ...typography.body,
    color: color.textPrimary,
    marginRight: space[2],
  },
  input: {
    flex: 1,
    ...typography.body,
    color: color.textPrimary,
    paddingVertical: space[3],
  },
  error: {
    ...typography.caption,
    color: color.error,
    marginTop: space[1],
  },
});
