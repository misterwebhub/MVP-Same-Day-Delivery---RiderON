<?php

namespace App\Filament\Resources\DeliveryPartnerResource\Pages;

use App\Filament\Resources\DeliveryPartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryPartners extends ListRecords
{
    protected static string $resource = DeliveryPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
