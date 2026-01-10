<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $ticket->subject }}</h2>
    </x-slot>

    <div class="container mx-auto py-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Category: <strong class="text-gray-800">{{ $ticket->category }}</strong> | Severity: <strong class="text-gray-800">{{ $ticket->severity }}</strong></p>
            </div>
            @php
                $statusClass = 'bg-gray-100 text-gray-800';
                if($ticket->status === \App\Models\Ticket::STATUS_OPEN) $statusClass = 'bg-green-100 text-green-800';
                if($ticket->status === \App\Models\Ticket::STATUS_IN_PROGRESS) $statusClass = 'bg-blue-100 text-blue-800';
                if($ticket->status === \App\Models\Ticket::STATUS_RESOLVED) $statusClass = 'bg-yellow-100 text-yellow-800';
                if($ticket->status === \App\Models\Ticket::STATUS_CLOSED) $statusClass = 'bg-gray-100 text-gray-800';
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </div>

        <div class="mt-4">
            <p class="text-gray-700">{{ $ticket->description }}</p>
        </div>

        <div class="mt-6 space-x-2">
            @can('claim', $ticket)
                <form method="POST" action="{{ route('tickets.claim', $ticket->id) }}" class="inline">
                    @csrf
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded mr-2 border border-gray-300 shadow-sm" style="background-color:#2563eb;color:#fff;padding:8px 12px;border-radius:6px;border:1px solid #1d4ed8;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Claim</button>
                </form>
            @endcan

            @can('resolve', $ticket)
                <form method="POST" action="{{ route('tickets.resolve', $ticket->id) }}" class="inline">
                    @csrf
                    <button class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded mr-2 border border-gray-300 shadow-sm" style="background-color:#f59e0b;color:#fff;padding:8px 12px;border-radius:6px;border:1px solid #d97706;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Resolve</button>
                </form>
            @endcan

            @can('close', $ticket)
                <form method="POST" action="{{ route('tickets.close', $ticket->id) }}" class="inline">
                    @csrf
                    <button class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-2 rounded border border-gray-300 shadow-sm" style="background-color:#374151;color:#fff;padding:8px 12px;border-radius:6px;border:1px solid #111827;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Close</button>
                </form>
            @endcan
        </div>

        <div class="mt-6 text-sm text-gray-700">
            <p>Assigned to: {{ optional($ticket->assignee)->name ?? 'Unassigned' }}</p>
            <p>Created by: {{ optional($ticket->creator)->name ?? 'Unknown' }}</p>
            <p class="mt-2">Created at: {{ $ticket->created_at->toDayDateTimeString() }}</p>
        </div>

        @can('assign', $ticket)
            <form method="POST" action="{{ route('tickets.assign', $ticket->id) }}" class="mt-3 inline-flex items-center gap-2">
                @csrf
                <label for="assignee_id" class="text-sm">Assign to:</label>
                <select name="assignee_id" id="assignee_id" class="border rounded p-1">
                    <option value="">Select agent</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->email }})</option>
                    @endforeach
                </select>
                <button class="bg-blue-600 text-white px-3 py-1 rounded">Assign</button>
            </form>
        @endcan

        @if($ticket->attachments && $ticket->attachments->count())
            <div class="mt-6">
                <h3 class="text-lg font-semibold">Attachments</h3>
                <ul class="mt-2 divide-y">
                    @foreach($ticket->attachments as $a)
                        @php $isImage = str_starts_with($a->mime ?? '', 'image/'); @endphp
                        <li class="py-4">
                            <div class="flex flex-col md:flex-row md:items-start md:space-x-6">
                                <div class="mb-3 md:mb-0 md:flex-shrink-0">
                                    @if($isImage)
                                        @php $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($a->path); @endphp
                                        @if($exists)
                                            <a href="{{ route('tickets.attachments.view', [$ticket->id, $a->id]) }}" style="display:block;max-width:680px">
                                                <div style="max-width:680px;">
                                                    <img src="{{ route('tickets.attachments.view', [$ticket->id, $a->id]) }}" alt="{{ $a->filename }}" style="max-width:680px;width:100%;height:auto;object-fit:contain;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.08);display:block" />
                                                </div>
                                            </a>
                                        @else
                                            <div class="text-xs text-red-500">Image file missing from public storage (run <code>php artisan storage:link</code>), or file unreachable; try downloading.</div>
                                        @endif
                                    @else
                                        <div class="w-48 border rounded p-2 text-sm text-gray-600">No preview available</div>
                                    @endif
                                </div>

                                <div class="flex-1">    
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800 break-words" style="font-weight:600;color:#111;margin-bottom:6px">{{ $a->filename }}</div>
                                            <div class="text-xs text-gray-500 mt-1" style="color:#6b7280;font-size:12px;margin-top:4px">{{ number_format($a->size / 1024, 2) }} KB • {{ $a->mime ?? '' }}</div>
                                        </div>
                                        <div class="text-xs text-gray-400">{{ $a->created_at->diffForHumans() }}</div>
                                    </div>
                                    <div class="mt-3 space-x-3">
                                        <a target="_blank" href="{{ route('tickets.attachments.view', [$ticket->id, $a->id]) }}" class="text-sm text-blue-600">Open</a>
                                        <button type="button" class="text-sm text-gray-600 attachment-diag-btn" data-url="{{ route('tickets.attachments.diag', [$ticket->id, $a->id]) }}">Debug</button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div id="attachment-diag" class="mt-3 text-xs text-gray-700 hidden"><pre id="attachment-diag-pre" class="p-2 bg-gray-50 border max-h-40 overflow-auto"></pre></div>
            </div>
        @endif
        </div>
    </div>

    <script>
    (function(){
        function $(s, el=document){ return el.querySelector(s); }
        const btns = document.querySelectorAll('.attachment-diag-btn');
        const diagWrap = $('#attachment-diag');
        const diagPre = $('#attachment-diag-pre');
        btns.forEach(b => {
            b.addEventListener('click', async (e) => {
                const url = b.dataset.url;
                diagWrap.classList.remove('hidden');
                diagPre.textContent = 'Loading...';
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                    const j = await res.json();
                    diagPre.textContent = JSON.stringify(j, null, 2);
                } catch (err){
                    diagPre.textContent = 'Fetch failed: '+ (err.message || err);
                }
            });
        });
    })();
    </script>
</x-app-layout>
