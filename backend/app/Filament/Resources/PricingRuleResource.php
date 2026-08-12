<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricingRuleResource\Pages;
use App\Filament\Resources\PricingRuleResource\RelationManagers;
use App\Filament\Support\AdminAccess;
use App\Models\PricingRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-rupee';

    protected static ?string $navigationGroup = 'Network';

    public static function canViewAny(): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public static function canCreate(): bool
    {
        return AdminAccess::canManagePricing();
    }

    public static function canEdit($record): bool
    {
        return AdminAccess::canManagePricing();
    }

    public static function canDelete($record): bool
    {
        return AdminAccess::canManagePricing();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('route_id')
                    ->relationship('route', 'id')
                    ->helperText('Leave empty for a global rule applied to all routes without a more specific override.')
                    ->default(null),
                Forms\Components\Select::make('rule_type')
                    ->options([
                        PricingRule::TYPE_BASE => 'Base fare',
                        PricingRule::TYPE_WEIGHT_SLAB => 'Weight slab',
                        PricingRule::TYPE_PEAK_HOUR => 'Peak hour surcharge',
                        PricingRule::TYPE_PLATFORM_FEE => 'Platform fee',
                        PricingRule::TYPE_TAX => 'Tax',
                    ])
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('min_weight_grams')
                    ->numeric()
                    ->visible(fn (Forms\Get $get): bool => $get('rule_type') === PricingRule::TYPE_WEIGHT_SLAB)
                    ->default(null),
                Forms\Components\TextInput::make('max_weight_grams')
                    ->numeric()
                    ->visible(fn (Forms\Get $get): bool => $get('rule_type') === PricingRule::TYPE_WEIGHT_SLAB)
                    ->default(null),
                Forms\Components\TextInput::make('amount_paise')
                    ->numeric()
                    ->helperText('Flat amount in paise. Used for base/weight_slab/peak_hour/platform_fee/tax rules that are not percentage-based.')
                    ->default(null),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->helperText('Percentage value. Used instead of amount_paise for percentage-based peak_hour/platform_fee/tax rules.')
                    ->visible(fn (Forms\Get $get): bool => in_array($get('rule_type'), [
                        PricingRule::TYPE_PEAK_HOUR,
                        PricingRule::TYPE_PLATFORM_FEE,
                        PricingRule::TYPE_TAX,
                    ], true))
                    ->default(null),
                Forms\Components\DateTimePicker::make('effective_from')
                    ->required(),
                Forms\Components\DateTimePicker::make('effective_to'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('route.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rule_type'),
                Tables\Columns\TextColumn::make('min_weight_grams')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_weight_grams')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paise')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_from')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_to')
                    ->dateTime()
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }
}
