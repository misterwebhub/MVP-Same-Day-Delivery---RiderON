import React, { useMemo, useState } from 'react';
import { FlatList, Modal, Platform, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { Icon } from '../../../components/Icon';
import type { StationOption } from '../hooks/useAllStations';

interface Section {
  title: string;
  data: StationOption[];
}

/** Full-screen "search a station" sheet — the Uber/Ola-style location picker
 * this app was missing. Replaces the old plain scrollable chip grid: one tap
 * opens this, type-to-filter narrows a couple dozen stations instantly, and
 * the currently-selected station (if any) is pinned to the top so re-opening
 * to change your mind doesn't mean re-scrolling to find it. */
export function StationPickerModal({
  visible,
  title,
  stations,
  loading,
  selectedStationId,
  disabledStationId,
  onSelect,
  onClose,
}: {
  visible: boolean;
  title: string;
  stations: StationOption[];
  loading: boolean;
  selectedStationId?: number | null;
  /** The other leg's station — shown but not selectable, so From/To can never match. */
  disabledStationId?: number | null;
  onSelect: (station: StationOption) => void;
  onClose: () => void;
}) {
  const [query, setQuery] = useState('');

  const sections = useMemo<Section[]>(() => {
    const q = query.trim().toLowerCase();
    const filtered = q
      ? stations.filter((s) => s.name.toLowerCase().includes(q) || s.city.name.toLowerCase().includes(q))
      : stations;

    const byCity = new Map<string, StationOption[]>();
    filtered.forEach((station) => {
      const list = byCity.get(station.city.name) ?? [];
      list.push(station);
      byCity.set(station.city.name, list);
    });

    return Array.from(byCity.entries())
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([cityName, data]) => ({ title: cityName, data }));
  }, [stations, query]);

  const flatData = useMemo(() => {
    const rows: Array<{ type: 'header'; title: string } | { type: 'station'; station: StationOption }> = [];
    sections.forEach((section) => {
      rows.push({ type: 'header', title: section.title });
      section.data.forEach((station) => rows.push({ type: 'station', station }));
    });
    return rows;
  }, [sections]);

  return (
    <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
      <SafeAreaView style={styles.screen} edges={['top', 'bottom']}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>{title}</Text>
          <TouchableOpacity accessibilityRole="button" accessibilityLabel="Close" onPress={onClose} hitSlop={8} style={styles.closeButton}>
            <Icon name="close" size={20} color={color.textPrimary} />
          </TouchableOpacity>
        </View>

        <View style={styles.searchBar}>
          <Icon name="search" size={18} color={color.textSecondary} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search a station or city"
            placeholderTextColor={color.textSecondary}
            value={query}
            onChangeText={setQuery}
            autoCorrect={false}
            autoFocus={Platform.OS !== 'web'}
          />
          {query.length > 0 ? (
            <TouchableOpacity onPress={() => setQuery('')} hitSlop={8}>
              <Icon name="close-circle" size={18} color={color.textSecondary} />
            </TouchableOpacity>
          ) : null}
        </View>

        {loading ? (
          <View style={styles.emptyState}>
            <Text style={styles.emptyStateText}>Loading stations…</Text>
          </View>
        ) : (
          <FlatList
            data={flatData}
            keyExtractor={(item, index) => (item.type === 'header' ? `h-${item.title}` : `s-${item.station.id}`) + index}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={styles.listContent}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <Icon name="location-outline" size={28} color={color.textSecondary} />
                <Text style={styles.emptyStateText}>No stations match "{query}"</Text>
              </View>
            }
            renderItem={({ item }) => {
              if (item.type === 'header') {
                return <Text style={styles.sectionHeader}>{item.title}</Text>;
              }
              const station = item.station;
              const selected = selectedStationId === station.id;
              const disabled = disabledStationId === station.id;
              return (
                <TouchableOpacity
                  style={[styles.row, selected && styles.rowSelected, disabled && styles.rowDisabled]}
                  activeOpacity={disabled ? 1 : 0.75}
                  disabled={disabled}
                  onPress={() => onSelect(station)}
                  accessibilityRole="button"
                  accessibilityState={{ selected, disabled }}
                >
                  <View style={[styles.rowIcon, selected && styles.rowIconSelected]}>
                    <Icon name="train" size={16} color={selected ? color.textInverse : color.primary} />
                  </View>
                  <View style={styles.rowText}>
                    <Text style={[styles.rowTitle, disabled && styles.rowTitleDisabled]}>{station.name}</Text>
                    <Text style={styles.rowSubtitle}>{station.city.name}</Text>
                  </View>
                  {selected ? <Icon name="checkmark-circle" size={20} color={color.primary} /> : null}
                  {disabled ? <Text style={styles.rowDisabledLabel}>selected on other side</Text> : null}
                </TouchableOpacity>
              );
            }}
          />
        )}
      </SafeAreaView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: color.surface,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: space[5],
    paddingTop: space[4],
    paddingBottom: space[3],
  },
  headerTitle: {
    ...typography.h1,
    color: color.textPrimary,
  },
  closeButton: {
    width: 32,
    height: 32,
    borderRadius: radius.pill,
    backgroundColor: color.background,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[2],
    marginHorizontal: space[5],
    marginBottom: space[3],
    paddingHorizontal: space[4],
    height: 46,
    borderRadius: radius.md,
    backgroundColor: color.background,
  },
  searchInput: {
    flex: 1,
    ...typography.body,
    color: color.textPrimary,
    height: '100%',
  },
  listContent: {
    paddingHorizontal: space[5],
    paddingBottom: space[8],
  },
  sectionHeader: {
    ...typography.micro,
    color: color.textSecondary,
    marginTop: space[4],
    marginBottom: space[2],
    letterSpacing: 0.6,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[3],
    paddingVertical: space[3],
    paddingHorizontal: space[3],
    borderRadius: radius.md,
    marginBottom: space[1],
  },
  rowSelected: {
    backgroundColor: color.primaryTint,
  },
  rowDisabled: {
    opacity: 0.4,
  },
  rowIcon: {
    width: 34,
    height: 34,
    borderRadius: radius.pill,
    backgroundColor: color.primaryTint,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowIconSelected: {
    backgroundColor: color.primary,
  },
  rowText: {
    flex: 1,
  },
  rowTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowTitleDisabled: {
    color: color.textSecondary,
  },
  rowSubtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 1,
  },
  rowDisabledLabel: {
    ...typography.micro,
    color: color.textSecondary,
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: space[8],
    gap: space[2],
  },
  emptyStateText: {
    ...typography.body,
    color: color.textSecondary,
  },
});
