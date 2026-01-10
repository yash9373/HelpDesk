<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ticket Queue</h2>
    </x-slot>

    <div class="container mx-auto py-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="GET" class="mb-4 flex gap-3 items-center">
                <select name="status" class="border rounded p-2">
                    <option value="">Status (open+in-progress)</option>
                    <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status')=='in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>Closed</option>
                </select>
                <label class="text-sm inline-flex items-center gap-2"><input type="checkbox" name="unassigned" value="1" {{ request('unassigned') ? 'checked' : '' }}>Unassigned</label>
                <label class="text-sm inline-flex items-center gap-2"><input type="checkbox" name="assigned_to_me" value="1" {{ request('assigned_to_me') ? 'checked' : '' }}>Assigned to me</label>
                <input type="text" name="q" placeholder="Search subject or description" value="{{ request('q') }}" class="flex-1 border rounded p-2" />
                <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded">Filter</button>
            </form>

            @if($tickets->count())
                <table class="w-full table-auto">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="p-2">Subject</th>
                            <th class="p-2">Category</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Created</th>
                            <th class="p-2">Assigned</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $t)
                            <tr class="border-b">
                                <td class="p-2"><a href="{{ route('tickets.show', $t->id) }}" class="font-semibold">{{ $t->subject }}</a>
                                    <div class="text-sm text-gray-600">{{ Str::limit($t->description, 120) }}</div>
                                </td>
                                <td class="p-2">{{ $t->category }}</td>
                                <td class="p-2">{{ $t->status }}</td>
                                <td class="p-2">{{ $t->created_at->diffForHumans() }} by {{ $t->creator?->name }}</td>
                                <td class="p-2">{{ $t->assignee?->name ?? '—' }}</td>
                                <td class="p-2">
                                    @can('claim', $t)
                                    <form method="POST" action="{{ route('tickets.claim', $t->id) }}" class="inline">
                                        @csrf
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded mr-2">Claim</button>
                                    </form>
                                    @endcan

                                    @can('resolve', $t)
                                    <form method="POST" action="{{ route('tickets.resolve', $t->id) }}" class="inline">
                                        @csrf
                                        <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded mr-2">Resolve</button>
                                    </form>
                                    @endcan

                                    @can('close', $t)
                                    <form method="POST" action="{{ route('tickets.close', $t->id) }}" class="inline">
                                        @csrf
                                        <button class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-1 rounded">Close</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">{{ $tickets->links() }}</div>
            @else
                <p class="text-sm text-gray-600">No tickets found.</p>
            @endif
        </div>
    </div>
</x-app-layout>