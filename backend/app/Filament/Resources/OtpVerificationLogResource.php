<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtpVerificationLogResource\Pages;
use App\Models\OtpVerificationLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only audit trail of pickup/delivery OTP verification attempts, used
 * by support/ops when investigating disputed pickups or deliveries.
 */
class OtpVerificationLogResource extends Resource
{
    protected static ?string $model = OtpVerificationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'OTP Logs';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('otpVerification.order_id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('otpVerification.purpose')
                    ->label('OTP purpose')
                    ->badge(),
                Tables\Columns\TextColumn::make('attempted_by_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('attempted_by_id')
                    ->label('Attempted by #'),
                Tables\Columns\TextColumn::make('result')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        OtpVerificationLog::RESULT_SUCCESS => 'success',
                        OtpVerificationLog::RESULT_INVALID => 'danger',
                        OtpVerificationLog::RESULT_EXPIRED => 'warning',
                        OtpVerificationLog::RESULT_LOCKED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ip_address'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('result')
                    ->options([
                        OtpVerificationLog::RESULT_SUCCESS => 'Success',
                        OtpVerificationLog::RESULT_INVALID => 'Invalid',
                        OtpVerificationLog::RESULT_EXPIRED => 'Expired',
                        OtpVerificationLog::RESULT_LOCKED => 'Locked',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOtpVerificationLogs::route('/'),
        ];
    }
}
