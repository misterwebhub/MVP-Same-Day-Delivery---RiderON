<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource\RelationManagers;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Support';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ticket_number')
                    ->required()
                    ->maxLength(20)
                    ->disabled(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'booking_reference')
                    ->disabled()
                    ->default(null),
                Forms\Components\Select::make('category')
                    ->options(array_combine(
                        array_map(fn (string $c) => $c, self::categories()),
                        array_map(fn (string $c) => ucwords(str_replace('_', ' ', $c)), self::categories()),
                    ))
                    ->disabled()
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->disabled()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        SupportTicket::STATUS_OPEN => 'Open',
                        SupportTicket::STATUS_IN_PROGRESS => 'In progress',
                        SupportTicket::STATUS_RESOLVED => 'Resolved',
                        SupportTicket::STATUS_CLOSED => 'Closed',
                    ])
                    ->live()
                    ->required(),
                Forms\Components\Select::make('assigned_to')
                    ->label('Assigned to')
                    ->relationship(
                        'assignedTo',
                        'name',
                        fn (Builder $query) => $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPS, User::ROLE_SUPPORT]),
                    )
                    ->default(null),
                Forms\Components\DateTimePicker::make('resolved_at')
                    ->visible(fn (Forms\Get $get): bool => in_array($get('status'), [
                        SupportTicket::STATUS_RESOLVED,
                        SupportTicket::STATUS_CLOSED,
                    ], true)),
            ]);
    }

    private static function categories(): array
    {
        return [
            SupportTicket::CATEGORY_PAYMENT,
            SupportTicket::CATEGORY_PICKUP,
            SupportTicket::CATEGORY_DELIVERY,
            SupportTicket::CATEGORY_RIDER,
            SupportTicket::CATEGORY_WRONG_PARCEL,
            SupportTicket::CATEGORY_DAMAGED_PARCEL,
            SupportTicket::CATEGORY_RECEIVER_UNAVAILABLE,
            SupportTicket::CATEGORY_CANCELLATION,
            SupportTicket::CATEGORY_REFUND,
            SupportTicket::CATEGORY_OTHER,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SupportTicket::STATUS_OPEN => 'danger',
                        SupportTicket::STATUS_IN_PROGRESS => 'warning',
                        SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned to')
                    ->default('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->dateTime()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SupportTicket::STATUS_OPEN => 'Open',
                        SupportTicket::STATUS_IN_PROGRESS => 'In progress',
                        SupportTicket::STATUS_RESOLVED => 'Resolved',
                        SupportTicket::STATUS_CLOSED => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options(array_combine(self::categories(), array_map(
                        fn (string $c) => ucwords(str_replace('_', ' ', $c)),
                        self::categories(),
                    ))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
