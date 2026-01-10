<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-2">Create a Ticket</h3>
                    <p class="text-sm text-gray-600 mb-4">Report an issue quickly so our agents can help.</p>
                    @can('create', \App\Models\Ticket::class)
                    <a href="{{ route('tickets.create') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded border border-green-700 shadow-sm" style="background-color:#16a34a;color:#ffffff;padding:8px 16px;border-radius:6px;border:1px solid #15803d;box-shadow:0 1px 2px rgba(0,0,0,0.06);text-decoration:none;">Create Ticket</a>
                    @endcan

                    @can('viewAny', \App\Models\Ticket::class)
                    <div class="mt-3">
                        <a href="{{ route('tickets.index') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded border border-blue-700 shadow-sm" style="background-color:#2563eb;color:#ffffff;padding:8px 16px;border-radius:6px;border:1px solid #1d4ed8;box-shadow:0 1px 2px rgba(0,0,0,0.06);text-decoration:none;">Manage Tickets</a>
                    </div>
                    @endcan

                    <div class="mt-3 text-xs text-gray-500">
                        <div>Role: <strong>{{ auth()->user()->role }}</strong></div>
                        <div>Can manage tickets: <strong>{{ auth()->user()->can('viewAny', \App\Models\Ticket::class) ? 'yes' : 'no' }}</strong></div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">My Recent Tickets</h3>
                    @if(isset($myTickets) && $myTickets->count())
                        <ul>
                            @foreach($myTickets as $t)
                                <li class="mb-3 border-b pb-2">
                                    <a href="{{ route('tickets.show', $t->id) }}" class="font-semibold">{{ $t->subject }}</a>
                                    <div class="text-sm text-gray-600">Status: {{ $t->status }} | Category: {{ $t->category }}</div>
                                    @can('update', $t)
                                        <a href="{{ route('tickets.edit', $t->id) }}" class="text-sm text-blue-600">Edit</a>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-600">You have not created any tickets yet.</p>
                    @endif

                    <h3 class="text-lg font-semibold mt-6 mb-4">Assigned to Me</h3>
                    @if(isset($assignedTickets) && $assignedTickets->count())
                        <ul>
                            @foreach($assignedTickets as $t)
                                <li class="mb-3 border-b pb-2">
                                    <a href="{{ route('tickets.show', $t->id) }}" class="font-semibold">{{ $t->subject }}</a>
                                    <div class="text-sm text-gray-600">Status: {{ $t->status }} | Category: {{ $t->category }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-600">No tickets assigned to you.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
