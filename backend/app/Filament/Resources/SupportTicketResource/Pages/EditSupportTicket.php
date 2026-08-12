<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $wasResolvedOrClosed = in_array($this->record->status, [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true);
        $isNowResolvedOrClosed = in_array($data['status'], [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true);

        if ($isNowResolvedOrClosed && ! $wasResolvedOrClosed) {
            $data['resolved_at'] = now();
        }

        if (! $isNowResolvedOrClosed) {
            $data['resolved_at'] = null;
        }

        return $data;
    }
}
