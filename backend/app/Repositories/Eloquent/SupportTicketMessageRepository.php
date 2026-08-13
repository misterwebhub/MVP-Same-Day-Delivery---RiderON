<?php

namespace App\Repositories\Eloquent;

use App\Models\SupportTicketMessage;
use App\Repositories\Contracts\SupportTicketMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SupportTicketMessageRepository extends BaseRepository implements SupportTicketMessageRepositoryInterface
{
    public function __construct(SupportTicketMessage $model)
    {
        parent::__construct($model);
    }

    public function forTicket(int $ticketId): Collection
    {
        return $this->model->newQuery()
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->get();
    }
}
