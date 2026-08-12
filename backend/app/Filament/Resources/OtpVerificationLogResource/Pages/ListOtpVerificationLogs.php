<?php

namespace App\Filament\Resources\OtpVerificationLogResource\Pages;

use App\Filament\Resources\OtpVerificationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtpVerificationLogs extends ListRecords
{
    protected static string $resource = OtpVerificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
