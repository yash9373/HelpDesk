<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Ticket</h2>
    </x-slot>

    <div class="container mx-auto py-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('tickets.update', $ticket->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            @if(session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                    <input id="subject" name="subject" value="{{ old('subject', $ticket->subject) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                    @error('subject') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-700">Severity</label>
                    <select id="severity" name="severity" class="mt-1 block w-full">
                        @for($i=1;$i<=5;$i++)
                            <option value="{{ $i }}" {{ $ticket->severity == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category" name="category" class="mt-1 block w-full">
                        <option value="access" {{ $ticket->category == 'access' ? 'selected' : '' }}>Access</option>
                        <option value="hardware" {{ $ticket->category == 'hardware' ? 'selected' : '' }}>Hardware</option>
                        <option value="network" {{ $ticket->category == 'network' ? 'selected' : '' }}>Network</option>
                        <option value="bug" {{ $ticket->category == 'bug' ? 'selected' : '' }}>Bug</option>
                        <option value="other" {{ $ticket->category == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" class="mt-1 block w-full">{{ old('description', $ticket->description) }}</textarea>
                    @error('description') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                @if($ticket->attachments && $ticket->attachments->count())
                    <div class="md:col-span-2 mb-4">
                        <h3 class="text-sm font-medium">Existing attachments</h3>
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($ticket->attachments as $a)
                                @php $isImage = str_starts_with($a->mime ?? '', 'image/'); @endphp
                                <div class="border p-2 rounded">
                                    @if($isImage)
                                        <img src="{{ Storage::disk('public')->url($a->path) }}" alt="{{ $a->filename }}" class="w-full h-32 object-cover rounded mb-2" />
                                    @endif
                                    <div class="text-xs text-gray-700 break-words">{{ $a->filename }}</div>
                                    <div class="mt-2 flex items-center space-x-2">
                                        <a href="{{ route('tickets.attachments.download', [$ticket->id, $a->id]) }}" class="text-blue-600 text-xs">Download</a>
                                        <button type="button" class="text-red-600 text-xs attachment-delete-btn" data-url="{{ route('tickets.attachments.destroy', [$ticket->id, $a->id]) }}">Delete</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="md:col-span-2">
                    <label for="attachments" class="block text-sm font-medium text-gray-700">Attachments</label>
                    <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full" accept=".png,.jpg,.jpeg,.pdf,.txt,.log" />
                    <p class="text-xs text-gray-500 mt-1">Allowed: png, jpg, jpeg, pdf, txt, log. Max 5MB each. Adding files will append to existing attachments.</p>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded border border-gray-300 shadow-sm">Update Ticket</button>
                </div>
            </div>
        </form>
        </div>
    </div>

    <script>
    (function(){
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        document.querySelectorAll('.attachment-delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                if (!confirm('Delete this attachment?')) return;
                btn.disabled = true;
                const url = btn.dataset.url;
                try{
                    const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }});
                    if (res.ok) {
                        // remove the parent border card
                        const card = btn.closest('.border');
                        if (card) card.remove();
                    } else {
                        const txt = await res.text();
                        alert('Delete failed: ' + txt);
                        btn.disabled = false;
                    }
                } catch (err){
                    alert('Delete failed: ' + err.message);
                    btn.disabled = false;
                }
            });
        });
    })();
    </script>
</x-app-layout>