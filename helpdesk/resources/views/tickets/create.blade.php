<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Ticket</h2>
    </x-slot>

    <div class="container mx-auto py-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf

            @if(session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700">{{ session('status') }}</div>
            @endif

        <div class="mb-4 relative">
            <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
            <input id="subject" name="subject" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" autocomplete="off" placeholder="e.g. Unable to access email" />
            <p class="text-xs text-gray-400 mt-1">Start typing the subject — suggestions will appear as you type.</p>
            <div id="suggestions" class="absolute left-0 right-0 mt-1 z-50 bg-white border rounded shadow max-h-60 overflow-auto hidden"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                <select id="category" name="category" class="mt-1 block w-full">
                    <option value="">— Any category —</option>
                    <option value="access">Access</option>
                    <option value="hardware">Hardware</option>
                    <option value="network">Network</option>
                    <option value="bug">Bug</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label for="severity" class="block text-sm font-medium text-gray-700">Severity</label>
                <select id="severity" name="severity" class="mt-1 block w-full">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3" selected>3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" name="description" class="mt-1 block w-full"></textarea>
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-3">
                    <!-- <button type="button" id="suggest-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded" style="background-color:#2563eb;color:#fff;padding:8px 10px;border-radius:6px;border:1px solid #1e40af;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Get suggestions</button>
                    <button type="button" id="suggest-debug-toggle" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-2 py-2 rounded text-sm">Debug</button> -->
                </div>

                <div id="suggest-debug" class="mt-2 hidden text-xs text-gray-600">
                    <pre id="suggest-debug-pre" class="max-h-40 overflow-auto p-2 bg-gray-50 border"></pre>
                </div>



                <div class="mb-4">
                    <label for="attachments" class="block text-sm font-medium text-gray-700">Attachments</label>
                    <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full" accept=".png,.jpg,.jpeg,.pdf,.txt,.log" />
                    <p class="text-xs text-gray-500 mt-1">Allowed: png, jpg, jpeg, pdf, txt, log. Max 5MB each.</p>
                </div>

                <div class="mb-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded border border-gray-300 shadow-sm" style="background-color:#16a34a;color:#fff;padding:8px 14px;border-radius:6px;border:1px solid #15803d;box-shadow:0 1px 2px rgba(0,0,0,0.06);">Create Ticket</button>
                </div>
            </div>
        </div>
    </form>
    </div>

    <script>
    (function(){
        // Lightweight debounce helper (avoids relying on module imports)
        function debounce(fn, wait){
            let t;
            return function(...a){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, a), wait); };
        }

        const subjectInput = document.getElementById('subject');
        const categoryInput = document.getElementById('category');
        const suggestionsDiv = document.getElementById('suggestions');
        const suggestBtn = document.getElementById('suggest-btn');
        const debugToggle = document.getElementById('suggest-debug-toggle');
        const debugWrap = document.getElementById('suggest-debug');
        const debugPre = document.getElementById('suggest-debug-pre');

        let selectedIndex = -1;

        function escapeHtml(s){
            return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function render(items){
            if (!items || items.length === 0){
                suggestionsDiv.innerHTML = '';
                suggestionsDiv.classList.add('hidden');
                return;
            }
            suggestionsDiv.classList.remove('hidden');
            suggestionsDiv.innerHTML = items.map((i, idx) => `
                <div class="p-2 border-b suggestion-item cursor-pointer flex justify-between" data-idx="${idx}" data-id="${i.ticket.id}" data-subject="${escapeHtml(i.ticket.subject)}" data-description="${escapeHtml(i.ticket.description)}" data-category="${i.ticket.category}" data-severity="${i.ticket.severity}">
                    <div>
                        <div class="font-semibold">${escapeHtml(i.ticket.subject)}</div>
                        <div class="text-sm text-gray-600">${i.snippet}</div>
                    </div>
                    <div class="text-xs text-gray-400">${i.score ? i.score.toFixed(2) : ''}</div>
                </div>
            `).join('');
            selectedIndex = -1;
        }

        async function fetchSuggestions(){
            const subject = subjectInput.value;
            const category = categoryInput.value;
            if (!subject.trim()) { render([]); return; }
            const url = new URL('{{ route('tickets.suggestions') }}', window.location.origin);
            url.searchParams.append('subject', subject);
            if (category && category.toString().trim() !== '') {
                url.searchParams.append('category', category);
            }
            // Shortlist rules (implemented server-side): ignore closed, limit to recent records, category-prefilter (applied only when category param is supplied)
            try{
                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
                if (!res.ok) { const txt = await res.text(); debugPre.textContent = 'HTTP ' + res.status + '\n' + txt; debugWrap.classList.remove('hidden'); render([]); return; }
                const items = await res.json();
                // update debug view (keeps hidden until the user presses Debug toggle)
                debugPre.textContent = JSON.stringify(items, null, 2);
                render(items);
            } catch (e){
                console.warn('Suggestion fetch failed', e);
                debugPre.textContent = 'Fetch error: ' + (e && e.message ? e.message : String(e));
                debugWrap.classList.remove('hidden');
                render([]);
            }
        }

        const debounced = debounce(fetchSuggestions, 250);
        subjectInput.addEventListener('input', debounced);
        suggestBtn.addEventListener('click', fetchSuggestions);

        subjectInput.addEventListener('keydown', (e) => {
            const items = suggestionsDiv.querySelectorAll('.suggestion-item');
            if (!items.length) return;
            if (e.key === 'ArrowDown'){
                e.preventDefault(); selectedIndex = Math.min(selectedIndex + 1, items.length - 1); highlight(items, selectedIndex);
            } else if (e.key === 'ArrowUp'){
                e.preventDefault(); selectedIndex = Math.max(selectedIndex - 1, 0); highlight(items, selectedIndex);
            } else if (e.key === 'Enter'){
                if (selectedIndex >= 0 && items[selectedIndex]){ e.preventDefault(); selectItem(items[selectedIndex]); }
            } else if (e.key === 'Escape'){
                suggestionsDiv.classList.add('hidden');
            }
        });

        function highlight(items, idx){
            items.forEach((el,i) => el.classList.toggle('bg-blue-50', i===idx));
            if (idx >= 0 && items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
        }

        function selectItem(el){
            subjectInput.value = el.dataset.subject || '';
            document.getElementById('description').value = el.dataset.description || '';
            document.getElementById('category').value = el.dataset.category || '';
            document.getElementById('severity').value = el.dataset.severity || '';
            suggestionsDiv.innerHTML = '';
            suggestionsDiv.classList.add('hidden');
            subjectInput.focus();
        }

        suggestionsDiv.addEventListener('click', (e) => {
            const el = e.target.closest('.suggestion-item');
            if (!el) return; selectItem(el);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#subject') && !e.target.closest('#suggestions') && !e.target.closest('#suggest-btn')){
                suggestionsDiv.classList.add('hidden');
            }
        });

        debugToggle.addEventListener('click', () => {
            debugWrap.classList.toggle('hidden');
        });
    })();
    </script>
</x-app-layout>
