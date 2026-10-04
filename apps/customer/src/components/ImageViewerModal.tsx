import React from 'react';
import { Image, Modal, Pressable, StyleSheet } from 'react-native';
import { color, space } from '@rideron/design-tokens';
import { Icon } from './Icon';

interface Props {
  /** The photo to show enlarged, or null to keep the modal hidden. */
  uri: string | null;
  onClose: () => void;
}

/** Full-screen tap-to-enlarge preview, used wherever a small parcel/proof
 * photo thumbnail is shown (Booking Summary, Order Details) — tapping the
 * thumbnail opens the photo bigger in an overlay; tapping anywhere closes it. */
export function ImageViewerModal({ uri, onClose }: Props) {
  return (
    <Modal visible={uri !== null} transparent animationType="fade" onRequestClose={onClose}>
      <Pressable style={styles.backdrop} onPress={onClose}>
        <Pressable style={styles.closeButton} onPress={onClose} hitSlop={12} accessibilityRole="button" accessibilityLabel="Close photo preview">
          <Icon name="close" size={26} color={color.textInverse} />
        </Pressable>
        {uri ? <Image source={{ uri }} style={styles.image} resizeMode="contain" /> : null}
      </Pressable>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(10,27,61,0.92)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  image: {
    width: '92%',
    height: '80%',
  },
  closeButton: {
    position: 'absolute',
    top: space[8],
    right: space[5],
    zIndex: 1,
  },
});
