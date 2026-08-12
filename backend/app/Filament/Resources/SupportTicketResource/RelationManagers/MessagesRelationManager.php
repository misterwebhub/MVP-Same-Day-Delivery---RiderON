<?php

namespace App\Filament\Resources\SupportTicketResource\RelationManagers;

use App\Models\SupportTicketMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('sender_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('message')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sender_type'] = SupportTicketMessage::SENDER_ADMIN;
                        $data['sender_id'] = auth()->id();
                        $data['created_at'] = now();

                        return $data;
                    }),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
