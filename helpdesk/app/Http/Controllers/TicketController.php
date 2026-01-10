<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Ticket::class);

        $query = \App\Models\Ticket::query();

        // Filters: status, unassigned, assigned_to_me, search
        if ($request->filled('status') && in_array($request->get('status'), [\App\Models\Ticket::STATUS_OPEN, \App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_RESOLVED, \App\Models\Ticket::STATUS_CLOSED])) {
            $query->where('status', $request->get('status'));
        } else {
            // default: show open and in_progress
            $query->whereIn('status', [\App\Models\Ticket::STATUS_OPEN, \App\Models\Ticket::STATUS_IN_PROGRESS]);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_to');
        }

        if ($request->boolean('assigned_to_me')) {
            $query->where('assigned_to', auth()->id());
        }

        if ($request->filled('q')) {
            $q = strtolower($request->get('q'));
            $query->where(function($s) use ($q) {
                $s->whereRaw('LOWER(subject) LIKE ?', ["%{$q}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$q}%"]);
            });
        }

        $tickets = $query->with('creator', 'assignee')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\Ticket::class);

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'severity' => 'nullable|integer|min:1|max:5',
            'attachments.*' => 'file|mimes:png,jpg,jpeg,pdf,txt,log|max:5120',
        ]);

        $data['created_by'] = auth()->id();
        $repo = app(\App\Repositories\TicketRepositoryInterface::class);
        $ticket = $repo->create($data);

        if ($request->hasFile('attachments')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('ticket_attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if (! $file->isValid()) continue;
                    $storedName = "ticket-{$ticket->id}-" . time() . "-" . \Illuminate\Support\Str::random(6) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("tickets/{$ticket->id}", $storedName, 'public');
                    \App\Models\TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'path' => $path,
                        'filename' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('ticket_attachments table missing, skipping attachment save');
            }
        }

        return redirect()->route('tickets.show', $ticket->id)->with('status', 'Ticket created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load('attachments', 'creator', 'assignee');
        $agents = \App\Models\User::where('role', 'agent')->orderBy('name')->get();
        return view('tickets.show', compact('ticket', 'agents'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        return view('tickets.edit', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'severity' => 'required|integer|min:1|max:5',
            'attachments.*' => 'file|mimes:png,jpg,jpeg,pdf,txt,log|max:5120',
        ]);

        $ticket->update($data);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (! $file->isValid()) continue;
                $stored = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $ext = $file->getClientOriginalExtension();
                $storedName = "ticket-{$ticket->id}-" . time() . "-" . \Illuminate\Support\Str::random(6) . ".$ext";
                $path = $file->storeAs("tickets/{$ticket->id}", $storedName, 'public');
                \App\Models\TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('tickets.show', $ticket->id)->with('status', 'Ticket updated');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function claim(Ticket $ticket)
    {
        $this->authorize('claim', $ticket);
        $ticket->update(['assigned_to' => auth()->id(), 'status' => \App\Models\Ticket::STATUS_IN_PROGRESS]);
        return back()->with('status', 'Ticket claimed');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('assign', $ticket);
        $data = $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        $assignee = \App\Models\User::find($data['assignee_id']);
        if (! $assignee || $assignee->role !== 'agent') {
            return back()->withErrors(['assignee_id' => 'Selected user is not an agent.']);
        }

        $ticket->update(['assigned_to' => $assignee->id, 'status' => \App\Models\Ticket::STATUS_IN_PROGRESS]);

        return back()->with('status', 'Ticket assigned to ' . $assignee->name);
    }

    public function resolve(Ticket $ticket)
    {
        $this->authorize('resolve', $ticket);
        $ticket->update(['status' => \App\Models\Ticket::STATUS_RESOLVED, 'resolved_at' => now()]);
        return back()->with('status', 'Ticket resolved');
    }

    public function close(Ticket $ticket)
    {
        $this->authorize('close', $ticket);
        $ticket->update(['status' => \App\Models\Ticket::STATUS_CLOSED, 'closed_at' => now()]);
        return back()->with('status', 'Ticket closed');
    }

    public function downloadAttachment(Ticket $ticket, \App\Models\TicketAttachment $attachment)
    {
        $this->authorize('view', $ticket);
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->download($attachment->path, $attachment->filename);
    }

    public function destroyAttachment(Ticket $ticket, \App\Models\TicketAttachment $attachment)
    {
        $this->authorize('update', $ticket);
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }
        try {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Could not delete attachment file: ' . $attachment->path);
        }
        $attachment->delete();
        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('status', 'Attachment deleted');
    }

    /**
     * Serve attachment inline (useful when storage:link is not available in dev)
     */
    public function viewAttachment(Ticket $ticket, \App\Models\TicketAttachment $attachment)
    {
        $this->authorize('view', $ticket);
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists($attachment->path)) {
            abort(404);
        }

        $full = $disk->path($attachment->path);
        // Return the file inline so images display in the browser
        return response()->file($full, [
            'Content-Type' => $attachment->mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $attachment->filename . '"'
        ]);
    }

    /**
     * Diagnostic info for attachments (exists?, paths, sizes) - temporary helper for debugging
     */
    public function diagAttachment(Ticket $ticket, \App\Models\TicketAttachment $attachment)
    {
        $this->authorize('view', $ticket);
        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json(['error' => 'not found'], 404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $exists = $disk->exists($attachment->path);
        $diskPath = $exists ? $disk->path($attachment->path) : null;
        $size = $exists ? $disk->size($attachment->path) : null;
        $publicUrl = null;
        try { $publicUrl = $disk->url($attachment->path); } catch (\Exception $e) { $publicUrl = null; }

        return response()->json([
            'exists' => $exists,
            'db_path' => $attachment->path,
            'disk_path' => $diskPath,
            'db_filename' => $attachment->filename,
            'mime' => $attachment->mime,
            'db_size' => $attachment->size,
            'disk_size' => $size,
            'view_url' => route('tickets.attachments.view', [$ticket->id, $attachment->id]),
            'download_url' => route('tickets.attachments.download', [$ticket->id, $attachment->id]),
            'public_url' => $publicUrl,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        //
    }
}
