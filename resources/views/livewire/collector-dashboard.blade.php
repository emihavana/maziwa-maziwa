{{--
    ALPINE: the root element holds ALL the Alpine state for this page.
    - modalOpen  : true/false, whether the collect modal is visible (no server request)
    - routeOpen  : true/false, whether the route dropdown is open
    - toast      : the success message popup (show flag, text, and the hide timer)
    - form       : the modal's data (farmer id, name, qty, notes, edit flag), filled when you click Collect
    - errors     : validation messages shown under the inputs (checked in the browser first)
    - saving     : true while the save request is running (disables the Save button)
    - openModal(): fills `form` from data already on the page, then shows the modal
    - closeModal(): hides the modal, no request
    - save()     : validates in the browser, then makes the ONE server request ($wire.saveCollection)
    - notify()   : shows the toast and hides it after 3 seconds
    Use `this.$wire` (not bare `$wire`) because we are inside an x-data method.
--}}
<div class="space-y-6 {{ $darkMode ? 'dark-theme' : '' }}"
    x-data="{
        modalOpen: false,
        routeOpen: false,

        {{-- ALPINE: toast state. `timer` holds the pending hide so a second save restarts the 3s countdown --}}
        toast: { show: false, message: '', timer: null },
        notify(message) {
            this.toast.message = message;
            this.toast.show = true;
            clearTimeout(this.toast.timer);
            {{-- ALPINE: auto-hide after 3000 ms (3 seconds). Change the number to adjust. --}}
            this.toast.timer = setTimeout(() => this.toast.show = false, 3000);
        },

        {{-- ALPINE: modal data lives here, so opening and closing make NO server request --}}
        form: { farmerId: '', name: '', qty: '', notes: '', edit: false },
        errors: {},
        saving: false,

        {{-- ALPINE: called by the Collect button with the farmer's data already on the page --}}
        openModal(farmer) {
            this.form = {
                farmerId: farmer.id,
                name: farmer.name,
                qty: farmer.qty ?? '',
                notes: farmer.notes ?? '',
                edit: farmer.edit
            };
            this.errors = {};
            this.modalOpen = true;
        },

        {{-- ALPINE: just hides the modal. Nothing is sent to the server. --}}
        closeModal() {
            this.modalOpen = false;
        },

        {{-- ALPINE: validates in the browser first (instant), then makes ONE request on Save --}}
        async save() {
            this.errors = {};
            if (!this.form.qty || parseFloat(this.form.qty) < 0.1) {
                this.errors.qty = 'Enter at least 0.1 litres.';
            }
            if ((this.form.notes || '').length > 100) {
                this.errors.notes = 'Note must be 100 characters or fewer.';
            }
            if (Object.keys(this.errors).length) return;

            this.saving = true;
            try {
                await this.$wire.saveCollection(this.form.farmerId, this.form.qty, this.form.notes);
            } finally {
                this.saving = false;
            }
        }
    }"
    {{-- ALPINE: Livewire dispatches 'collection-saved' after a successful save; we close the modal --}}
    @collection-saved.window="modalOpen = false"
    {{-- ALPINE: Livewire dispatches 'notify' with a message; we show the toast --}}
    @notify.window="notify($event.detail.message)"
    {{-- ALPINE: pressing Esc closes the modal (only if it is open) --}}
    @keydown.escape.window="modalOpen && closeModal()">

    {{-- Page header --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-slate-900">Collector Dashboard</h1>
            <p class="text-sm text-slate-500">Manage route collections, view farmers, and update records in real time.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">

            {{-- "Back to Admin" button, only rendered for admins ($isAdmin comes from the component) --}}
            @if($isAdmin)
            <a href="{{ $adminUrl }}"
                class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                &larr; admin
            </a>
            @endif

            {{--
                ALPINE: route dropdown.
                - @click.outside closes it when you click anywhere else
                - the button toggles `routeOpen`
                - choosing an option calls the Livewire selectRoute() method, then closes the menu
            --}}
            <div class="relative" @click.outside="routeOpen = false">
                <button type="button" @click="routeOpen = !routeOpen"
                    class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    <span>{{ $selectedRouteName }}</span>
                    {{-- ALPINE: :class rotates the arrow when the menu is open --}}
                    <svg class="h-4 w-4 transition" :class="routeOpen && 'rotate-180'" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                {{-- ALPINE: x-show shows/hides the menu; x-cloak hides it before Alpine loads (no flash) --}}
                <div x-show="routeOpen" x-cloak x-transition.origin.top.right
                    class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-lg">
                    <button type="button" @click="$wire.selectRoute(null); routeOpen = false"
                        class="block w-full px-4 py-2 text-left text-sm hover:bg-slate-100 {{ is_null($selectedRouteId) ? 'font-semibold text-slate-900' : 'text-slate-700' }}">
                        All routes
                    </button>
                    @foreach($routes as $route)
                    <button type="button" @click="$wire.selectRoute({{ $route->id }}); routeOpen = false"
                        class="block w-full px-4 py-2 text-left text-sm hover:bg-slate-100 {{ $selectedRouteId === $route->id ? 'font-semibold text-slate-900' : 'text-slate-700' }}">
                        {{ $route->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <button wire:click="toggleDarkMode"
                class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                {{ $darkMode ? 'Light Mode' : 'Dark Mode' }}
            </button>
            <button wire:click="logout"
                class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Logout
            </button>
        </div>
    </div>

    {{-- Section tabs --}}
    <div class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <button wire:click="selectSection('overview')"
            class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeSection === 'overview' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200' }}">
            Overview
        </button>
        <button wire:click="selectSection('farmers')"
            class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeSection === 'farmers' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200' }}">
            Farmers
        </button>
    </div>

    {{-- ===================== OVERVIEW ===================== --}}
    @if($activeSection === 'overview')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Selected Route</div>
            <div class="mt-3 text-xl font-semibold text-slate-900">{{ $selectedRouteName ?? 'All routes' }}</div>
            <button wire:click="selectRoute(null)"
                class="mt-4 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Clear route
            </button>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Today's Milk Collected</div>
            <div class="mt-3 text-3xl font-semibold text-emerald-600">{{ number_format($totalCollectedQty, 2) }} L</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Farmers Collected</div>
            <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $collectedCount }}</div>
            <div class="text-sm text-slate-500">{{ $pendingCount }} pending</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Total Route Farmers</div>
            <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $totalFarmersRoute }}</div>
            <div class="text-sm text-slate-500">Remaining: {{ $remainingFarmersRoute }}</div>
        </div>
    </div>
    @endif

    {{-- ===================== FARMERS ===================== --}}
    @if($activeSection === 'farmers')
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <div>
            <h2 class="text-lg font-semibold text-slate-900">Farmers</h2>
            <p class="text-sm text-slate-500">Tap collect to add or update today's milk entry.</p>
        </div>

        <div class="mt-4">
            {{-- Livewire v3 syntax: .live is required for typing to trigger a search request --}}
            <input wire:model.live.debounce.250ms="searchQuery" type="search"
                placeholder="Search by farmer name or ID"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:outline-none" />
        </div>

        {{-- Farmer cards: stacks vertically on all screens, action button always visible, no horizontal scroll --}}
        <div class="mt-4 space-y-3">
            @forelse($farmers as $farmer)
            @php
            $collection = $farmer->milkCollections->first();
            $collected = $collection !== null;
            $buttonLabel = $collected
                ? ($collection->collector_id === auth()->id() ? 'Update' : 'Correct')
                : 'Collect';
            // ALPINE: everything the modal needs, built here on the server and handed to
            // Alpine's openModal() when you click, so opening needs no server request.
            $payload = [
                'id' => $farmer->farmer_id,
                'name' => $farmer->name,
                'qty' => $collection?->qty,
                'notes' => $collection?->correction_reason,
                'edit' => $collected,
            ];
            @endphp
            <div
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">

                {{-- Farmer identity --}}
                <div class="min-w-0">
                    <div class="truncate font-semibold text-slate-900">{{ $farmer->name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $farmer->farmer_id }} &middot; {{ $farmer->route?->name ?? 'Unassigned' }}
                    </div>
                </div>

                {{-- Status + action: same row on mobile so button is always visible without scrolling --}}
                <div class="flex items-center justify-between gap-3 sm:justify-end sm:shrink-0">
                    @if($collected)
                    <span
                        class="inline-flex items-center whitespace-nowrap rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        {{ number_format($collection->qty, 2) }} L
                    </span>
                    @else
                    <span
                        class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        Pending
                    </span>
                    @endif

                    {{--
                        ALPINE: opens the modal instantly with this farmer's data. No server request.
                        @js() safely converts the PHP array into a JavaScript object.
                    --}}
                    <button type="button" @click="openModal(@js($payload))"
                        class="shrink-0 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        {{ $buttonLabel }}
                    </button>
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-slate-500">
                No farmers found for this route.
            </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $farmers->links() }}
        </div>
    </div>
    @endif

    {{--
        ALPINE: success toast. x-show="toast.show" displays it when notify() runs; notify() hides it
        again after 3 seconds. x-text fills in the message. z-[60] keeps it above the modal (z-50).
    --}}
    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg">
        <span x-text="toast.message"></span>
    </div>

    {{--
        ALPINE: the modal.
        - x-show="modalOpen" shows/hides it (x-cloak prevents a flash on page load)
        - @click.self closes it when you click the dark backdrop (but not the white box)
        - it sits outside the Farmers @if block so its state survives tab switches
    --}}
    <div x-show="modalOpen" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeModal()">

        <div x-show="modalOpen" x-transition
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    {{-- ALPINE: x-text reads from the Alpine `form` object, so there is no "Loading..." delay --}}
                    <h2 class="text-xl font-semibold text-slate-900"
                        x-text="form.edit ? 'Update Collection' : 'Collect Milk'"></h2>
                    <p class="text-sm text-slate-500" x-text="form.name"></p>
                </div>
                {{-- ALPINE: closes the modal, no server request --}}
                <button type="button" @click="closeModal()"
                    class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">
                    Close
                </button>
            </div>

            {{-- ALPINE: @submit.prevent stops the normal page submit and runs Alpine's save() instead --}}
            <form @submit.prevent="save()" class="mt-6 space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Quantity (Liters)</label>
                    {{-- ALPINE: x-model keeps this input in sync with form.qty (two-way binding) --}}
                    <input x-model="form.qty" type="number" step="0.01" min="0.1"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:outline-none" />
                    {{-- ALPINE: browser-side error, shown instantly --}}
                    <p x-show="errors.qty" x-text="errors.qty" class="mt-2 text-sm text-rose-600"></p>
                    {{-- Server-side safety net, in case the server rejects the value --}}
                    @error('qty')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Note</label>
                    {{-- ALPINE: x-model keeps this textarea in sync with form.notes --}}
                    <textarea x-model="form.notes" rows="3"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:outline-none"></textarea>
                    <p x-show="errors.notes" x-text="errors.notes" class="mt-2 text-sm text-rose-600"></p>
                    @error('notes')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- Stacks to full-width buttons on mobile so nothing gets cut off --}}
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    {{-- ALPINE: Cancel closes the modal, no server request --}}
                    <button type="button" @click="closeModal()"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Cancel
                    </button>
                    {{-- ALPINE: :disabled and the two x-show labels follow the `saving` flag (prevents double submits) --}}
                    <button type="submit" :disabled="saving"
                        class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        <span x-show="!saving">Save</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>