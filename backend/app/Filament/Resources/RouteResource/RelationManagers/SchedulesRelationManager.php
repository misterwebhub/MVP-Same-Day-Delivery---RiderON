<?php

namespace App\Filament\Resources\RouteResource\RelationManagers;

use App\Filament\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    private const DAYS = [
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
    ];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TimePicker::make('departure_time')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('arrival_time')
                    ->seconds(false)
                    ->required(),
                Forms\Components\CheckboxList::make('days_of_week')
                    ->options(self::DAYS)
                    ->columns(4)
                    ->required(),
                Forms\Components\TextInput::make('booking_cutoff_minutes_before')
                    ->numeric()
                    ->required()
                    ->default(60),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('departure_time')
            ->columns([
                Tables\Columns\TextColumn::make('departure_time'),
                Tables\Columns\TextColumn::make('arrival_time'),
                Tables\Columns\TextColumn::make('days_of_week')
                    ->formatStateUsing(fn (array $state): string => collect($state)
                        ->map(fn ($day) => self::DAYS[(int) $day] ?? $day)
                        ->implode(', ')),
                Tables\Columns\TextColumn::make('booking_cutoff_minutes_before')
                    ->label('Cutoff (min)'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => AdminAccess::canManageMasterData()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => AdminAccess::canManageMasterData()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => AdminAccess::canManageMasterData()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(fn (): bool => AdminAccess::canManageMasterData()),
            ]);
    }
}
