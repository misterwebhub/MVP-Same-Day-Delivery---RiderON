import React, { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, statusBadgeColor, typography } from '@rideron/design-tokens';
import type { Order, OrderStatus, RouteSummary } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { formatPaise } from '../../../utils/currency';
import { Card } from '../../../components/Card';
import { Icon, IconName } from '../../../components/Icon';
import { Logo } from '../../../components/Logo';
import type { AppTabsParamList, RootStackParamList } from '../../../navigation/types';

type Props = CompositeScreenProps<
  BottomTabScreenProps<AppTabsParamList, 'Home'>,
  NativeStackScreenProps<RootStackParamList>
>;

/** Order has moved past payment and isn't finished yet — worth a banner on Home. */
const ACTIVE_DELIVERY_STATUSES: OrderStatus[] = [
  'BOOKED',
  'RIDER_ASSIGNMENT_PENDING',
  'RIDER_ASSIGNED',
  'WAITING_FOR_PICKUP',
  'RIDER_ARRIVED_PICKUP',
  'PICKUP_OTP_PENDING',
  'PICKED_UP',
  'IN_TRANSIT',
  'ARRIVED_DESTINATION',
  'WAITING_FOR_RECEIVER',
  'DELIVERY_OTP_PENDING',
  'DELIVERED',
];

const HOW_IT_WORKS: { icon: IconName; text: string }[] = [
  { icon: 'location', text: 'Pick a route, a slot, and tell us what you’re sending.' },
  { icon: 'card', text: 'Pay securely — your order is confirmed once payment clears.' },
  { icon: 'shield-checkmark', text: 'Share the Pickup/Delivery OTP only with the verified rider.' },
];

const WHY_CHOOSE: { icon: IconName; title: string; subtitle: string }[] = [
  { icon: 'rocket', title: 'Fast Delivery', subtitle: 'Same day' },
  { icon: 'shield-checkmark', title: 'Secure Handling', subtitle: '100% Safe' },
  { icon: 'navigate', title: 'Live Tracking', subtitle: 'Real-time updates' },
  { icon: 'pricetag', title: 'Affordable Price', subtitle: 'Best rates' },
];

export function Home({ navigation }: Props) {
  const [orders, setOrders] = useState<Order[] | null>(null);
  const [popularRoutes, setPopularRoutes] = useState<RouteSummary[] | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    const [ordersResult, routesResult] = await Promise.allSettled([
      apiClient.orders.list({ per_page: 5 }),
      apiClient.catalog.getPopularRoutes(),
    ]);
    if (ordersResult.status === 'fulfilled') {
      setOrders(ordersResult.value.items);
    }
    if (routesResult.status === 'fulfilled') {
      setPopularRoutes(routesResult.value);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  const activeOrder = orders?.find((o) => ACTIVE_DELIVERY_STATUSES.includes(o.status));
  const recentOrders = orders?.slice(0, 3) ?? [];

  const goToBooking = () => navigation.getParent()?.navigate('Booking');
  const goToOrder = (orderId: number) => navigation.getParent()?.navigate('OrderDetails', { orderId });
  const goToOrders = () => navigation.navigate('Orders');
  const goToSupport = () => navigation.navigate('Support');

  const quickActions: { icon: IconName; label: string; bg: string; iconColor: string; onPress: () => void }[] = [
    { icon: 'file-tray-full', label: 'My Bookings', bg: color.tintOrangeBg, iconColor: color.tintOrangeIcon, onPress: goToOrders },
    {
      icon: 'navigate-circle',
      label: 'Track Parcel',
      bg: color.tintBlueBg,
      iconColor: color.tintBlueIcon,
      onPress: () => (activeOrder ? goToOrder(activeOrder.id) : goToOrders()),
    },
    { icon: 'pricetag', label: 'Get a Quote', bg: color.tintGreenBg, iconColor: color.tintGreenIcon, onPress: goToBooking },
    { icon: 'headset', label: 'Support', bg: color.tintPurpleBg, iconColor: color.tintPurpleIcon, onPress: goToSupport },
  ];

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <TouchableOpacity accessibilityRole="button" accessibilityLabel="Open profile menu" onPress={() => navigation.navigate('Profile')} hitSlop={8}>
          <Icon name="menu" size={24} color={color.secondary} />
        </TouchableOpacity>
        <Logo size={26} variant="dark" />
        <TouchableOpacity
          accessibilityRole="button"
          accessibilityLabel="Open notifications"
          style={styles.bell}
          onPress={() => navigation.navigate('Profile', { screen: 'Notifications' })}
          hitSlop={8}
        >
          <Icon name="notifications-outline" size={22} color={color.secondary} />
          {(orders?.length ?? 0) > 0 && <View style={styles.bellBadge} />}
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
      >
        <Card style={styles.bookingCard}>
          <Text style={styles.bookingCardTitle}>Book Your Parcel</Text>
          <Text style={styles.bookingCardSubtitle}>Station to Station Delivery</Text>

          <TouchableOpacity style={styles.bookingRow} onPress={goToBooking} activeOpacity={0.85}>
            <View style={styles.bookingRowIcon}>
              <Icon name="radio-button-on" size={16} color={color.primary} />
            </View>
            <Text style={styles.bookingRowText} numberOfLines={1}>
              {activeOrder?.route?.origin_station?.name ?? popularRoutes?.[0]?.origin_station?.name ?? 'Choose pickup station'}
            </Text>
            <Icon name="chevron-forward" size={18} color={color.textSecondary} />
          </TouchableOpacity>
          <TouchableOpacity style={styles.bookingRow} onPress={goToBooking} activeOpacity={0.85}>
            <View style={styles.bookingRowIcon}>
              <Icon name="location" size={16} color={color.primary} />
            </View>
            <Text style={styles.bookingRowText} numberOfLines={1}>
              {activeOrder?.route?.destination_station?.name ?? popularRoutes?.[0]?.destination_station?.name ?? 'Choose drop station'}
            </Text>
            <Icon name="chevron-forward" size={18} color={color.textSecondary} />
          </TouchableOpacity>

          <TouchableOpacity style={styles.bookNowButton} onPress={goToBooking} activeOpacity={0.85}>
            <Text style={styles.bookNowText}>Book Now</Text>
            <Icon name="arrow-forward" size={18} color={color.textInverse} />
          </TouchableOpacity>
        </Card>

        <View style={styles.quickActions}>
          {quickActions.map((action) => (
            <TouchableOpacity key={action.label} style={styles.quickAction} onPress={action.onPress} activeOpacity={0.8}>
              <View style={[styles.quickActionIcon, { backgroundColor: action.bg }]}>
                <Icon name={action.icon} size={22} color={action.iconColor} />
              </View>
              <Text style={styles.quickActionLabel}>{action.label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {activeOrder ? (
          <TouchableOpacity style={styles.activeBanner} onPress={() => goToOrder(activeOrder.id)} activeOpacity={0.85}>
            <View
              style={[
                styles.activeDot,
                { backgroundColor: statusBadgeColor[activeOrder.status as keyof typeof statusBadgeColor] ?? color.info },
              ]}
            />
            <View style={styles.activeBannerText}>
              <Text style={styles.activeBannerTitle}>Order {activeOrder.booking_reference} is on the way</Text>
              <Text style={styles.activeBannerStatus}>{activeOrder.status.replace(/_/g, ' ')}</Text>
            </View>
            <Icon name="chevron-forward" size={20} color={color.textSecondary} />
          </TouchableOpacity>
        ) : null}

        <View style={styles.promoBanner}>
          <View style={styles.promoBannerText}>
            <Text style={styles.promoBannerTitle}>Fast. Safe. Reliable.</Text>
            <Text style={styles.promoBannerHighlight}>Station to Station Parcel Delivery</Text>
            <Text style={styles.promoBannerSubtitle}>Across Kanpur & Lucknow</Text>
          </View>
          <View style={styles.promoBannerIcon}>
            <Icon name="train" size={40} color={color.primary} />
          </View>
        </View>

        {recentOrders.length > 0 ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Recent orders</Text>
            {recentOrders.map((order) => (
              <TouchableOpacity key={order.id} style={styles.orderRow} onPress={() => goToOrder(order.id)} activeOpacity={0.85}>
                <View style={styles.orderRowText}>
                  <Text style={styles.orderRowRef}>{order.booking_reference}</Text>
                  <Text style={styles.orderRowRoute} numberOfLines={1}>
                    {order.route?.origin_station?.name ?? '—'} → {order.route?.destination_station?.name ?? '—'}
                  </Text>
                </View>
                <Text style={styles.orderRowAmount}>{formatPaise(order.total_amount_paise)}</Text>
              </TouchableOpacity>
            ))}
          </View>
        ) : null}

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>How it works</Text>
          {HOW_IT_WORKS.map((step) => (
            <View key={step.text} style={styles.howItWorksRow}>
              <View style={styles.howItWorksIcon}>
                <Icon name={step.icon} size={18} color={color.primary} />
              </View>
              <Text style={styles.howItWorksText}>{step.text}</Text>
            </View>
          ))}
        </View>

        {popularRoutes && popularRoutes.length > 0 ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Popular routes</Text>
            {popularRoutes.slice(0, 5).map((route) => (
              <TouchableOpacity key={route.id} style={styles.routeRow} onPress={goToBooking} activeOpacity={0.85}>
                <Icon name="train-outline" size={18} color={color.textSecondary} />
                <Text style={styles.routeRowText} numberOfLines={1}>
                  {route.origin_station?.name ?? '—'} → {route.destination_station?.name ?? '—'}
                </Text>
                <Text style={styles.routeRowPrice}>from {formatPaise(route.price_from)}</Text>
              </TouchableOpacity>
            ))}
          </View>
        ) : null}

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Why Choose RiderON?</Text>
          <View style={styles.whyGrid}>
            {WHY_CHOOSE.map((item) => (
              <View key={item.title} style={styles.whyItem}>
                <Icon name={item.icon} size={22} color={color.secondary} />
                <Text style={styles.whyTitle}>{item.title}</Text>
                <Text style={styles.whySubtitle}>{item.subtitle}</Text>
              </View>
            ))}
          </View>
        </View>

        <TouchableOpacity style={styles.supportShortcut} onPress={goToSupport} activeOpacity={0.85}>
          <Text style={styles.supportShortcutText}>Need help with an order? Contact support →</Text>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: color.background,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: space[5],
    paddingTop: space[6],
    paddingBottom: space[3],
    backgroundColor: color.surface,
    borderBottomWidth: 1,
    borderBottomColor: color.border,
  },
  bell: {
    position: 'relative',
  },
  bellBadge: {
    position: 'absolute',
    top: -2,
    right: -2,
    width: 8,
    height: 8,
    borderRadius: radius.pill,
    backgroundColor: color.error,
  },
  container: {
    flex: 1,
  },
  content: {
    padding: space[5],
    paddingBottom: space[8],
  },
  bookingCard: {
    marginBottom: space[4],
  },
  bookingCardTitle: {
    ...typography.h1,
    color: color.textPrimary,
  },
  bookingCardSubtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[4],
  },
  bookingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: color.border,
    borderRadius: radius.md,
    paddingHorizontal: space[3],
    paddingVertical: space[3],
    marginBottom: space[3],
  },
  bookingRowIcon: {
    width: 28,
    alignItems: 'center',
  },
  bookingRowText: {
    ...typography.bodyStrong,
    color: color.textPrimary,
    flex: 1,
  },
  bookNowButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: color.primary,
    borderRadius: radius.md,
    paddingVertical: space[4],
    marginTop: space[1],
    gap: space[1],
  },
  bookNowText: {
    ...typography.bodyStrong,
    color: color.textInverse,
  },
  quickActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: space[4],
  },
  quickAction: {
    alignItems: 'center',
    flex: 1,
  },
  quickActionIcon: {
    width: 48,
    height: 48,
    borderRadius: radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: space[1],
  },
  quickActionLabel: {
    ...typography.caption,
    color: color.textPrimary,
    textAlign: 'center',
  },
  activeBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: color.primaryTint,
    borderRadius: radius.md,
    padding: space[4],
    marginBottom: space[4],
  },
  activeDot: {
    width: 10,
    height: 10,
    borderRadius: radius.pill,
    marginRight: space[3],
  },
  activeBannerText: {
    flex: 1,
  },
  activeBannerTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  activeBannerStatus: {
    ...typography.caption,
    color: color.textSecondary,
    textTransform: 'capitalize',
  },
  promoBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: color.secondary,
    borderRadius: radius.lg,
    padding: space[5],
    marginBottom: space[6],
    overflow: 'hidden',
  },
  promoBannerText: {
    flex: 1,
  },
  promoBannerTitle: {
    ...typography.h2,
    color: color.textInverse,
  },
  promoBannerHighlight: {
    ...typography.bodyStrong,
    color: color.primary,
    marginTop: space[1],
  },
  promoBannerSubtitle: {
    ...typography.caption,
    color: color.textInverse,
    opacity: 0.7,
    marginTop: space[1],
  },
  promoBannerIcon: {
    width: 64,
    height: 64,
    borderRadius: radius.pill,
    backgroundColor: color.secondaryTint,
    alignItems: 'center',
    justifyContent: 'center',
  },
  section: {
    marginBottom: space[6],
  },
  sectionTitle: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
  },
  orderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[2],
  },
  orderRowText: {
    flex: 1,
    marginRight: space[3],
  },
  orderRowRef: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  orderRowRoute: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  orderRowAmount: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  howItWorksRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: space[3],
  },
  howItWorksIcon: {
    width: 36,
    height: 36,
    borderRadius: radius.pill,
    backgroundColor: color.primaryTint,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: space[3],
  },
  howItWorksText: {
    ...typography.body,
    color: color.textSecondary,
    flex: 1,
  },
  routeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[2],
    paddingVertical: space[3],
    borderBottomWidth: 1,
    borderBottomColor: color.border,
  },
  routeRowText: {
    ...typography.body,
    color: color.textPrimary,
    flex: 1,
  },
  routeRowPrice: {
    ...typography.caption,
    color: color.textSecondary,
  },
  whyGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: space[3],
  },
  whyItem: {
    width: '47%',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
  },
  whyTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
    marginTop: space[2],
  },
  whySubtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  supportShortcut: {
    alignItems: 'center',
    padding: space[4],
  },
  supportShortcutText: {
    ...typography.bodyStrong,
    color: color.primary,
  },
});
