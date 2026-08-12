<?php

namespace App\Filament\Resources\DeliveryPartnerResource\Pages;

use App\Filament\Resources\DeliveryPartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryPartner extends EditRecord
{
    protected static string $resource = DeliveryPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
