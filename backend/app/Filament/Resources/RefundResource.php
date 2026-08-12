<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RefundResource\Pages;
use App\Filament\Support\AdminAccess;
use App\Models\Refund;
use App\Services\Refunds\RefundProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use RuntimeException;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Payments';

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
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'booking_reference')
                    ->disabled(),
                Forms\Components\Select::make('payment_id')
                    ->relationship('payment', 'id')
                    ->disabled(),
                Forms\Components\TextInput::make('requested_by')
                    ->disabled(),
                Forms\Components\TextInput::make('reason')
                    ->disabled(),
                Forms\Components\TextInput::make('amount_paise')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\TextInput::make('provider_refund_id')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.booking_reference')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment.id')
                    ->label('Payment #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_by')
                    ->badge(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(40),
                Tables\Columns\TextColumn::make('amount_paise')
                    ->label('Amount (paise)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Refund::STATUS_PENDING => 'warning',
                        Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING => 'info',
                        Refund::STATUS_COMPLETED => 'success',
                        Refund::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('provider_refund_id')
                    ->label('Provider ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved by')
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Refund::STATUS_PENDING => 'Pending',
                        Refund::STATUS_APPROVED => 'Approved',
                        Refund::STATUS_PROCESSING => 'Processing',
                        Refund::STATUS_COMPLETED => 'Completed',
                        Refund::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve & process')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Refund $record): bool => AdminAccess::canApproveRefunds() && $record->status === Refund::STATUS_PENDING)
                    ->action(function (Refund $record): void {
                        try {
                            app(RefundProcessor::class)->approve($record, auth()->id());

                            Notification::make()
                                ->title('Refund processed')
                                ->success()
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title('Refund could not be processed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Refund $record): bool => AdminAccess::canApproveRefunds() && $record->status === Refund::STATUS_PENDING)
                    ->action(function (Refund $record): void {
                        $record->forceFill([
                            'status' => Refund::STATUS_REJECTED,
                            'approved_by' => auth()->id(),
                        ])->save();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefunds::route('/'),
            'view' => Pages\ViewRefund::route('/{record}'),
        ];
    }
}
