<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface SupportTicketMessageRepositoryInterface extends RepositoryInterface
{
    public function forTicket(int $ticketId): Collection;
}
