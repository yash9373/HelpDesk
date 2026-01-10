<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'agent';
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->role === 'agent' || $ticket->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['agent', 'employee']);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $ticket->created_by === $user->id && $ticket->status === Ticket::STATUS_OPEN;
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->role === 'agent' && $ticket->status === Ticket::STATUS_OPEN && is_null($ticket->assigned_to);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->role === 'agent' && $ticket->status === Ticket::STATUS_OPEN;
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->role === 'agent' && $ticket->assigned_to === $user->id && $ticket->status === Ticket::STATUS_IN_PROGRESS;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->role === 'agent' && $ticket->status === Ticket::STATUS_RESOLVED;
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->role === 'agent' && $ticket->status === Ticket::STATUS_CLOSED;
    }
}
