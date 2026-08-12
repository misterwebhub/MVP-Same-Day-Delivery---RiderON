<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;
use App\Filament\Resources\RouteResource\RelationManagers;
use App\Filament\Support\AdminAccess;
use App\Models\Route;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Network';

    public static function canCreate(): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public static function canEdit($record): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public static function canDelete($record): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('origin_station_id')
                    ->relationship('originStation', 'name')
                    ->required(),
                Forms\Components\Select::make('destination_station_id')
                    ->relationship('destinationStation', 'name')
                    ->required(),
                Forms\Components\TextInput::make('distance_km')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('estimated_duration_minutes')
                    ->required()
                    ->numeric(),
                Forms\Components\TimePicker::make('cutoff_time')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TextInput::make('max_parcels_per_schedule')
                    ->required()
                    ->numeric()
                    ->default(50),
                Forms\Components\TextInput::make('waiting_time_minutes')
                    ->required()
                    ->numeric()
                    ->default(30),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('originStation.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('destinationStation.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distance_km')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cutoff_time'),
                Tables\Columns\TextColumn::make('max_parcels_per_schedule')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waiting_time_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
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
