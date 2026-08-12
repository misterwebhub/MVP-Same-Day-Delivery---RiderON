<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryPartnerResource\Pages;
use App\Filament\Resources\DeliveryPartnerResource\RelationManagers;
use App\Filament\Support\AdminAccess;
use App\Models\DeliveryPartner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryPartnerResource extends Resource
{
    protected static ?string $model = DeliveryPartner::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Operations';

    public static function canCreate(): bool
    {
        return AdminAccess::canManageOrders();
    }

    public static function canEdit($record): bool
    {
        return AdminAccess::canManageOrders();
    }

    public static function canDelete($record): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('partner_code')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('photo_url')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Select::make('vehicle_type')
                    ->options([
                        DeliveryPartner::VEHICLE_TRAIN => 'Train',
                        DeliveryPartner::VEHICLE_BUS => 'Bus',
                        DeliveryPartner::VEHICLE_BIKE => 'Bike',
                        DeliveryPartner::VEHICLE_ON_FOOT => 'On foot',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('id_proof_type')
                    ->maxLength(50)
                    ->default(null),
                Forms\Components\Textarea::make('id_proof_number_encrypted')
                    ->columnSpanFull(),
                Forms\Components\Select::make('verification_status')
                    ->options([
                        DeliveryPartner::VERIFICATION_PENDING => 'Pending',
                        DeliveryPartner::VERIFICATION_VERIFIED => 'Verified',
                        DeliveryPartner::VERIFICATION_REJECTED => 'Rejected',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Forms\Components\TextInput::make('rating_avg')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('completed_deliveries_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('current_home_city_id')
                    ->relationship('currentHomeCity', 'name')
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('photo_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_type'),
                Tables\Columns\TextColumn::make('id_proof_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('verification_status'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rating_avg')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_deliveries_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currentHomeCity.name')
                    ->numeric()
                    ->sortable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryPartners::route('/'),
            'create' => Pages\CreateDeliveryPartner::route('/create'),
            'edit' => Pages\EditDeliveryPartner::route('/{record}/edit'),
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
