{{--
    CHANGED: root element now holds the Alpine state.
    - modalOpen : Alpine controls the modal (was $showModal in Livewire)
    - routeOpen : controls the route dropdown
    - closeModal(): hides the modal, then asks Livewire to clear the form data.
      Note `this.$wire` (not bare `$wire`) because we are inside an x-data method.
    - @collection-saved.window : Livewire dispatches this after a successful save, so we close the modal.
    - @keydown.escape.window : pressing Esc closes the modal.
--}}
<div class="space-y-6 {{ $darkMode ? 'dark-theme' : '' }}"
    x-data="{
        modalOpen: false,
        routeOpen: false,
        {{-- CHANGED: toast state. timer holds the pending hide so a second save restarts the 3s countdown --}}
        toast: { show: false, message: '', timer: null },
        notify(message) {
            this.toast.message = message;
            this.toast.show = true;
            clearTimeout(this.toast.timer);
            {{-- CHANGED: auto-hide after 3000 ms (3 seconds). Change the number to adjust. --}}
            this.toast.timer = setTimeout(() => this.toast.show = false, 3000);
        },
        closeModal() {
            this.modalOpen = false;
            this.$wire.resetModal();
        }
    }"
    @collection-saved.window="modalOpen = false"
    {{-- CHANGED: listens for the 'notify' event dispatched by saveCollection() in the component --}}
    @notify.window="notify($event.detail.message)"
    @keydown.escape.window="modalOpen && closeModal()">

    {{-- Page header --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-slate-900">Collector Dashboard</h1>
            <p class="text-sm text-slate-500">Manage route collections, view farmers, and update records in real time.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">

            {{-- CHANGED: new "Back to Admin" button, only rendered for admins ($isAdmin comes from the component) --}}
            @if($isAdmin)
            <a href="{{ $adminUrl }}"
                class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                &larr; Back to Admin
            </a>
            @endif

            {{--
                CHANGED: new Alpine route dropdown. It replaces the old "Show All Routes" button
                and the Routes sidebar. @click.outside closes it when clicking elsewhere.
                Selecting an option calls the Livewire selectRoute() method, then closes the menu.
            --}}
            <div class="relative" @click.outside="routeOpen = false">
                <button type="button" @click="routeOpen = !routeOpen"
                    class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    <span>{{ $selectedRouteName }}</span>
                    <svg class="h-4 w-4 transition" :class="routeOpen && 'rotate-180'" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

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
            {{-- CHANGED: removed the "Show All Routes" button; "All routes" is now inside the dropdown --}}
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
    {{-- CHANGED: the Routes sidebar is gone (the dropdown replaces it), so the list is now full width --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        {{-- Header row: title --}}
        {{-- CHANGED: removed the "Show all" button; use the route dropdown instead --}}
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Farmers</h2>
            <p class="text-sm text-slate-500">Tap collect to add or update today's milk entry.</p>
        </div>

        <div class="mt-4">
            {{-- CHANGED: wire:model.debounce.250ms -> wire:model.live.debounce.250ms (Livewire v3+ syntax) --}}
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
                        CHANGED: was wire:click="openCollectModal('...')".
                        Alpine opens the modal immediately (modalOpen = true) and Livewire loads the
                        farmer's data in the background. @js() safely quotes the farmer_id (e.g. 'F0009').
                    --}}
                    <button type="button"
                        @click="modalOpen = true; $wire.openCollectModal(@js($farmer->farmer_id))"
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
        CHANGED: success toast. Shown when Livewire dispatches 'notify', hidden automatically
        after 3 seconds by the notify() method above. z-[60] keeps it above the modal (z-50).
    --}}
    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg">
        <span x-text="toast.message"></span>
    </div>

    {{--
        CHANGED: the modal is now driven by Alpine instead of @if($showModal).
        - x-show="modalOpen" shows/hides it (x-cloak prevents a flash on page load)
        - @click.self closes it when clicking the dark backdrop
        - It sits outside the Farmers @if block so its state survives tab switches
    --}}
    <div x-show="modalOpen" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeModal()">

        <div x-show="modalOpen" x-transition
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        {{ $isEditMode ? 'Update Collection' : 'Collect Milk' }}
                    </h2>
                    {{-- CHANGED: shows "Loading..." while Livewire fetches the farmer after the modal opens --}}
                    <p class="text-sm text-slate-500">
                        <span wire:loading.remove wire:target="openCollectModal">{{ $selectedFarmerName }}</span>
                        <span wire:loading wire:target="openCollectModal">Loading...</span>
                    </p>
                </div>
                {{-- CHANGED: was wire:click="closeModal"; now Alpine closeModal() --}}
                <button type="button" @click="closeModal()"
                    class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200">
                    Close
                </button>
            </div>

            {{-- CHANGED: wire:submit.prevent -> wire:submit (v3 prevents default automatically) --}}
            <form wire:submit="saveCollection" class="mt-6 space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Quantity (Liters)</label>
                    {{-- CHANGED: wire:model.defer -> wire:model (.defer was removed in v3; plain model is deferred by default) --}}
                    <input wire:model="qty" type="number" step="0.01" min="0.1"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:outline-none" />
                    @error('qty')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Note</label>
                    <textarea wire:model="notes" rows="3"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:outline-none"></textarea>
                    @error('notes')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Assign Route</label>
                    <select wire:model="selectedFarmerRouteId"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:outline-none">
                        <option value="">Keep current route</option>
                        @foreach($routes as $route)
                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                        @endforeach
                    </select>
                </div> --}}

                {{-- Stacks to full-width buttons on mobile so nothing gets cut off --}}
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    {{-- CHANGED: Cancel now uses Alpine closeModal() instead of wire:click --}}
                    <button type="button" @click="closeModal()"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Cancel
                    </button>
                    {{-- CHANGED: Save button is disabled and shows "Saving..." while the request runs (prevents double submits) --}}
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveCollection"
                        class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveCollection">Save</span>
                        <span wire:loading wire:target="saveCollection">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>