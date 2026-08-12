<?php

namespace App\Filament\Resources;

use App\Constants\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Support\AdminAccess;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Refunds\RefundProcessor;
use App\StateMachines\OrderStateMachine;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Orders are created only by the customer booking flow and progress only via
 * OrderStateMachine transitions — there is no free-text status field or
 * generic edit form here. Admin/ops act through the named actions below,
 * each of which calls the state machine (or RefundProcessor) directly so
 * every change is validated and recorded in order_status_history exactly
 * like the customer- and partner-facing flows.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Operations';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_reference')
                    ->disabled(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->disabled(),
                Forms\Components\Select::make('route_id')
                    ->relationship('route', 'id')
                    ->disabled(),
                Forms\Components\Select::make('route_schedule_id')
                    ->relationship('routeSchedule', 'id')
                    ->disabled(),
                Forms\Components\Select::make('partner_id')
                    ->relationship('partner', 'id')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\DatePicker::make('booking_date')
                    ->disabled(),
                Forms\Components\TextInput::make('sender_name')
                    ->disabled(),
                Forms\Components\TextInput::make('sender_phone')
                    ->disabled(),
                Forms\Components\TextInput::make('sender_landmark')
                    ->disabled(),
                Forms\Components\TextInput::make('receiver_name')
                    ->disabled(),
                Forms\Components\TextInput::make('receiver_phone')
                    ->disabled(),
                Forms\Components\TextInput::make('receiver_landmark')
                    ->disabled(),
                Forms\Components\Textarea::make('price_breakdown')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_amount_paise')
                    ->disabled(),
                Forms\Components\TextInput::make('currency')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('cancelled_at')
                    ->disabled(),
                Forms\Components\TextInput::make('cancellation_reason')
                    ->disabled(),
                Forms\Components\TextInput::make('cancelled_by')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('arrived_destination_at')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('waiting_deadline_at')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('delivered_at')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('route')
                    ->label('Route')
                    ->state(fn (Order $record): string => $record->route
                        ? "{$record->route->originStation?->name} \u{2192} {$record->route->destinationStation?->name}"
                        : '—'),
                Tables\Columns\TextColumn::make('partner.user.name')
                    ->label('Partner')
                    ->default('Unassigned'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        OrderStatus::COMPLETED, OrderStatus::DELIVERED => 'success',
                        OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED, OrderStatus::DISPUTED, OrderStatus::FAILED_DELIVERY => 'danger',
                        OrderStatus::REFUND_PENDING, OrderStatus::REFUNDED, OrderStatus::RIDER_ASSIGNMENT_PENDING => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('booking_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount_paise')
                    ->label('Amount (paise)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_combine(OrderStatus::ALL, OrderStatus::ALL)),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('assignPartner')
                    ->label('Assign partner')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (Order $record): bool => AdminAccess::canManageOrders()
                        && $record->status === OrderStatus::RIDER_ASSIGNMENT_PENDING
                        && $record->partner_id === null)
                    ->form(fn (Order $record): array => [
                        Forms\Components\Select::make('partner_id')
                            ->label('Delivery partner')
                            ->options(fn (): array => static::eligiblePartners($record))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->forceFill(['partner_id' => $data['partner_id']])->save();

                        Notification::make()
                            ->title('Partner assigned')
                            ->body('The partner still needs to accept the assignment in their app.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('forceCancel')
                    ->label('Force cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => AdminAccess::canManageOrders() && ! OrderStatus::isTerminal($record->status))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation reason')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(OrderStateMachine::class)->transition(
                                $record,
                                OrderStatus::CANCELLED,
                                OrderStatusHistory::ACTOR_ADMIN,
                                auth()->id(),
                                [
                                    'cancelled_at' => now(),
                                    'cancellation_reason' => $data['reason'],
                                    'cancelled_by' => Order::CANCELLED_BY_ADMIN,
                                ],
                            );

                            Notification::make()
                                ->title('Order cancelled')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Could not cancel order')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('markDisputed')
                    ->label('Mark disputed')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => AdminAccess::canManageOrders()
                        && in_array($record->status, app(OrderStateMachine::class)->allowedSourceStatuses(OrderStatus::DISPUTED), true))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Dispute reason')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(OrderStateMachine::class)->transition(
                                $record,
                                OrderStatus::DISPUTED,
                                OrderStatusHistory::ACTOR_ADMIN,
                                auth()->id(),
                                [],
                                ['reason' => $data['reason']],
                            );

                            Notification::make()
                                ->title('Order marked disputed')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Could not mark order disputed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('approveRefund')
                    ->label('Approve refund')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => AdminAccess::canApproveRefunds() && $record->status === OrderStatus::REFUND_PENDING)
                    ->form(fn (Order $record): array => [
                        Forms\Components\TextInput::make('amount_paise')
                            ->label('Refund amount (paise)')
                            ->numeric()
                            ->required()
                            ->default($record->total_amount_paise),
                        Forms\Components\Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(RefundProcessor::class)->createAndProcess(
                                $record,
                                (int) $data['amount_paise'],
                                $data['reason'],
                                auth()->id(),
                            );

                            app(OrderStateMachine::class)->transition(
                                $record,
                                OrderStatus::REFUNDED,
                                OrderStatusHistory::ACTOR_ADMIN,
                                auth()->id(),
                            );

                            Notification::make()
                                ->title('Refund processed')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Refund could not be processed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    /**
     * Mirrors PartnerAssignmentService::attemptAssignment()'s eligibility
     * query, but returns every match (not just the best one) so an admin
     * can pick manually.
     *
     * @return array<int, string>
     */
    private static function eligiblePartners(Order $order): array
    {
        $order->loadMissing('route.originStation');
        $originCityId = $order->route->originStation->city_id;

        $busyPartnerIds = Order::query()
            ->where('id', '!=', $order->id)
            ->where('booking_date', $order->booking_date)
            ->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED, OrderStatus::COMPLETED])
            ->whereNotNull('partner_id')
            ->pluck('partner_id');

        return DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->where('current_home_city_id', $originCityId)
            ->whereNotIn('id', $busyPartnerIds)
            ->with('user')
            ->orderBy('completed_deliveries_count')
            ->get()
            ->mapWithKeys(fn (DeliveryPartner $partner): array => [
                $partner->id => "{$partner->user?->name} ({$partner->partner_code}) — {$partner->completed_deliveries_count} deliveries",
            ])
            ->all();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StatusHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
