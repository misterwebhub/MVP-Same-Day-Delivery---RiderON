<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Repositories\Contracts\SupportTicketMessageRepositoryInterface;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tickets are created only by the customer support-request flow — there is
 * no create form here, mirroring the removed Filament SupportTicketResource
 * (canCreate() implicitly true there via EditAction only in the table, but
 * no CreateSupportTicket page was reachable from the UI; kept edit-only to
 * match actual admin capability).
 */
class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly SupportTicketMessageRepositoryInterface $messages,
    ) {}

    public function index(Request $request): View
    {
        $tickets = $this->tickets->paginate(15, $request->only(['status', 'category', 'assigned_to', 'search']));

        return view('admin.support-tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'category', 'assigned_to', 'search']),
            'categories' => $this->categories(),
        ]);
    }

    public function edit(SupportTicket $supportTicket): View
    {
        $supportTicket->load(['customer', 'order', 'assignedTo']);

        return view('admin.support-tickets.edit', [
            'ticket' => $supportTicket,
            'categories' => $this->categories(),
            'assignees' => User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPS, User::ROLE_SUPPORT])->orderBy('name')->get(),
            'messages' => $this->messages->forTicket($supportTicket->id),
        ]);
    }

    public function update(SupportTicketRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $this->tickets->update($supportTicket, $request->validated());

        return redirect()->route('admin.support-tickets.edit', $supportTicket)->with('status', 'Ticket updated.');
    }

    public function storeMessage(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $supportTicket->id,
            'sender_type' => SupportTicketMessage::SENDER_ADMIN,
            'sender_id' => auth()->id(),
            'message' => $data['message'],
            'created_at' => now(),
        ]);

        return redirect()->route('admin.support-tickets.edit', $supportTicket)->with('status', 'Message sent.');
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        $categories = [
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

        return array_combine($categories, array_map(
            fn (string $c) => ucwords(str_replace('_', ' ', $c)),
            $categories,
        ));
    }
}
