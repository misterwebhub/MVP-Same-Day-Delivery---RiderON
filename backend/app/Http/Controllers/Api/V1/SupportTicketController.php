<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\CreateSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Services\Support\TicketNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function __construct(private readonly TicketNumberGenerator $ticketNumberGenerator)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('customer_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return $this->success([
            'items' => SupportTicketResource::collection($tickets->items()),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    public function store(CreateSupportTicketRequest $request): JsonResponse
    {
        $orderId = $request->integer('order_id') ?: null;

        if ($orderId !== null) {
            $order = Order::query()->findOrFail($orderId);
            abort_unless($order->customer_id === $request->user()->id, 403);
        }

        $ticket = SupportTicket::create([
            'ticket_number' => $this->ticketNumberGenerator->generate(),
            'customer_id' => $request->user()->id,
            'order_id' => $orderId,
            'category' => $request->string('category')->toString(),
            'description' => $request->string('description')->toString(),
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Support ticket created.', 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->customer_id === $request->user()->id, 403);

        $ticket->load('messages');

        return $this->success(new SupportTicketResource($ticket));
    }
}
