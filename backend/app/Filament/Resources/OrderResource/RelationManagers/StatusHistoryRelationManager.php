<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only audit trail of every status transition, for admin/ops
 * investigating a disputed or stuck order.
 */
class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    protected static ?string $title = 'Status history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->columns([
                Tables\Columns\TextColumn::make('from_status')
                    ->badge()
                    ->color('gray')
                    ->default('—'),
                Tables\Columns\TextColumn::make('to_status')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('changed_by_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('changed_by_id')
                    ->label('Changed by #')
                    ->default('—'),
                Tables\Columns\TextColumn::make('metadata')
                    ->label('Details')
                    ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state) : '—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
