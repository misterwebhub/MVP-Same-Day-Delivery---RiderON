@extends('admin.layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.support-tickets.index') }}" class="link-quiet">&larr; Back to tickets</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">{{ $ticket->ticket_number }}</h2>
            <p class="page-subtitle">{{ $ticket->customer?->name }} &middot; {{ $categories[$ticket->category] ?? $ticket->category }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <h3 class="mb-4 text-body-strong text-text-primary">Ticket details</h3>

            <dl class="mb-4 grid grid-cols-2 gap-4 text-body">
                <div>
                    <dt class="text-caption text-text-secondary">Customer</dt>
                    <dd>{{ $ticket->customer?->name }} ({{ $ticket->customer?->phone }})</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Order</dt>
                    <dd>
                        @if ($ticket->order)
                            <a href="{{ route('admin.orders.show', $ticket->order) }}" class="text-primary hover:underline">{{ $ticket->order->booking_reference }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Category</dt>
                    <dd>{{ $categories[$ticket->category] ?? $ticket->category }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-caption text-text-secondary">Description</dt>
                    <dd>{{ $ticket->description }}</dd>
                </div>
            </dl>

            <h3 class="mb-2 mt-6 text-body-strong text-text-primary">Messages</h3>
            <div class="mb-4 max-h-96 space-y-3 overflow-y-auto">
                @forelse ($messages as $message)
                    <div class="rounded-md border border-border p-3 {{ $message->sender_type === 'admin' ? 'bg-primary/5' : 'bg-surface' }}">
                        <div class="mb-1 flex items-center justify-between text-caption text-text-secondary">
                            <span class="font-medium">{{ ucfirst($message->sender_type) }}</span>
                            <span>{{ $message->created_at }}</span>
                        </div>
                        <p class="text-body">{{ $message->message }}</p>
                    </div>
                @empty
                    <div class="empty-state">No messages yet.</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.support-tickets.messages.store', $ticket) }}" class="space-y-3">
                @csrf
                <textarea name="message" required placeholder="Reply to customer..." class="form-input" rows="3"></textarea>
                <button type="submit" class="btn-primary">Send reply</button>
            </form>
        </div>

        <div class="card">
            <h3 class="mb-3 text-body-strong text-text-primary">Ticket status</h3>
            <form method="POST" action="{{ route('admin.support-tickets.update', $ticket) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" required class="form-input">
                        <option value="open" @selected(old('status', $ticket->status) === 'open')>Open</option>
                        <option value="in_progress" @selected(old('status', $ticket->status) === 'in_progress')>In progress</option>
                        <option value="resolved" @selected(old('status', $ticket->status) === 'resolved')>Resolved</option>
                        <option value="closed" @selected(old('status', $ticket->status) === 'closed')>Closed</option>
                    </select>
                </div>

                <div>
                    <label for="assigned_to" class="form-label">Assigned to</label>
                    <select id="assigned_to" name="assigned_to" class="form-input">
                        <option value="">Unassigned</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $assignee->id)>
                                {{ $assignee->name }} ({{ ucfirst($assignee->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="resolved_at" class="form-label">Resolved at</label>
                    <input id="resolved_at" type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($ticket->resolved_at)->format('Y-m-d\TH:i')) }}" class="form-input">
                </div>

                <button type="submit" class="btn-primary w-full">Save changes</button>
            </form>
        </div>
    </div>
@endsection
