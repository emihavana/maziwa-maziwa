<div class="space-y-6" x-data="{ 
        darkMode: @entangle('darkMode'),
        modalOpen: false,
        routeOpen: false,
        toast: { show: false, message: '', timer: null },
        notify(message) {
            this.toast.message = message;
            this.toast.show = true;
            clearTimeout(this.toast.timer);
            this.toast.timer = setTimeout(() => this.toast.show = false, 3000);
        },
        form: { farmerId: '', name: '', qty: '', notes: '', edit: false },
        errors: {},
        saving: false,
        openModal(farmer) {
            this.form = { farmerId: farmer.id, name: farmer.name, qty: farmer.qty ?? '', notes: farmer.notes ?? '', edit: farmer.edit };
            this.errors = {};
            this.modalOpen = true;
        },
        closeModal() { this.modalOpen = false; },
        async save() {
            this.errors = {};
            if (!this.form.qty || parseFloat(this.form.qty) < 0.1) this.errors.qty = 'Enter at least 0.1 litres.';
            if ((this.form.notes || '').length > 100) this.errors.notes = 'Note must be 100 characters or fewer.';
            if (Object.keys(this.errors).length) return;
            this.saving = true;
            try {
                await this.$wire.saveCollection(this.form.farmerId, this.form.qty, this.form.notes);
            } finally {
                this.saving = false;
            }
        }
    }" x-init="$watch('darkMode', value => {
        if (value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })" x-destroy="
        document.documentElement.classList.remove('dark');
    " @collection-saved.window="modalOpen = false" @notify.window="notify($event.detail.message)"
    @keydown.escape.window="modalOpen && closeModal()" @date-changed.window="closeModal()">

    {{-- Page header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Collector Dashboard</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage route collections, view farmers, and update
                records in real time.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">

            @if(auth()->check() && auth()->user()->isAdmin())
            <button type="button" wire:click="backToAdmin"
                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                &larr; Back to Admin
            </button>
            @endif

            @if(auth()->check() && auth()->user()->isAdmin())
            <div class="flex items-center gap-2">
                <label for="collectionDate"
                    class="text-sm font-semibold text-slate-700 dark:text-slate-300">Date</label>
                <div class="relative">
                    <input id="collectionDate" type="date" wire:model.live="collectionDate"
                        max="{{ now()->toDateString() }}"
                        class="cursor-pointer rounded-full border border-slate-200 bg-white px-4 py-2 pl-10 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:[color-scheme:dark]" />
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500 dark:text-slate-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            @endif

            <div class="relative" @click.outside="routeOpen = false">
                <button type="button" @click="routeOpen = !routeOpen"
                    class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <span>{{ $selectedRouteName }}</span>
                    <svg class="h-4 w-4 transition-transform text-slate-500 dark:text-slate-400"
                        :class="routeOpen && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="routeOpen" x-cloak x-transition.origin.top.right
                    class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-600 dark:bg-slate-800">
                    <button type="button" @click="$wire.selectRoute(null); routeOpen = false"
                        class="block w-full px-4 py-2 text-left text-sm transition hover:bg-slate-50 dark:hover:bg-slate-700 {{ is_null($selectedRouteId) ? 'font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-700 dark:text-slate-300' }}">All
                        routes</button>
                    @foreach($routes as $route)
                    <button type="button" @click="$wire.selectRoute({{ $route->id }}); routeOpen = false"
                        class="block w-full px-4 py-2 text-left text-sm transition hover:bg-slate-50 dark:hover:bg-slate-700 {{ $selectedRouteId === $route->id ? 'font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-700 dark:text-slate-300' }}">{{
                        $route->name }}</button>
                    @endforeach
                </div>
            </div>

            <button wire:click="toggleDarkMode"
                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                {{ $darkMode ? 'Light Mode' : 'Dark Mode' }}
            </button>
            <button wire:click="logout"
                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-red-50 hover:text-red-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-red-900/30 dark:hover:text-red-400">Logout</button>
        </div>
    </div>

    <p class="text-sm text-slate-500 dark:text-slate-400">
        Showing collections for
        <span class="font-bold text-emerald-600 dark:text-emerald-400">
            {{ \Carbon\Carbon::parse($collectionDate)->isToday() ? 'Today (' .
            \Carbon\Carbon::parse($collectionDate)->format('M j, Y') . ')' :
            \Carbon\Carbon::parse($collectionDate)->format('D, M j, Y') }}
        </span>
    </p>

    <div
        class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <button wire:click="selectSection('overview')"
            class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 {{ $activeSection === 'overview' ? 'bg-slate-900 text-white shadow-md dark:bg-slate-600' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700/50' }}">Overview</button>
        <button wire:click="selectSection('farmers')"
            class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 {{ $activeSection === 'farmers' ? 'bg-slate-900 text-white shadow-md dark:bg-slate-600' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700/50' }}">Farmers</button>
    </div>

    {{-- ===================== OVERVIEW ===================== --}}
    @if($activeSection === 'overview')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Selected Route</div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white truncate">{{ $selectedRouteName ?? 'All
                routes' }}</div>
            @if($selectedRouteId)
            <button wire:click="selectRoute(null)"
                class="mt-4 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">Clear
                route</button>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{
                \Carbon\Carbon::parse($collectionDate)->isToday() ? "Today's Milk" : 'Milk Collected' }}</div>
            <div class="mt-2 text-3xl font-extrabold text-emerald-500 dark:text-emerald-400">{{
                number_format($totalCollectedQty, 2) }} <span class="text-xl">L</span></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Farmers Collected</div>
            <div class="mt-2 text-3xl font-extrabold text-blue-600 dark:text-blue-400">{{ $collectedCount }}</div>
            <div
                class="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 inline-block px-2 py-0.5 rounded-full">
                {{ $pendingCount }} pending</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Route Farmers</div>
            <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">{{ $totalFarmersRoute }}</div>
            <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Remaining: {{
                $remainingFarmersRoute }}</div>
        </div>
    </div>
    @endif
    {{-- ===================== FARMERS ===================== --}}
    @if($activeSection === 'farmers')
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Farmers Directory</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Tap collect to add or update today's milk entry.</p>
        </div>

        <div class="relative mb-4">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input wire:model.live.debounce.250ms="searchQuery" type="search" placeholder="Search by farmer name or ID"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500 dark:focus:bg-slate-800" />
        </div>

        {{-- CHANGED: space-y-1.5 for tighter stacking --}}
        <div class="space-y-1.5">
            @forelse($farmers as $farmer)
            @php
            $collection = $farmer->milkCollections->first();
            $collected = $collection !== null;
            $buttonLabel = $collected ? ($collection->collector_id === auth()->id() ? 'Update' : 'Correct') : 'Collect';
            $payload = ['id' => $farmer->farmer_id, 'name' => $farmer->name, 'qty' => $collection?->qty, 'notes' =>
            $collection?->correction_reason, 'edit' => $collected];
            @endphp

            {{-- CHANGED: py-2.5 px-4 instead of p-4. Darker bg for dark mode. --}}
            <div
                class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white py-2.5 px-4 transition-all hover:border-slate-300 hover:shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 sm:flex-row sm:items-center sm:justify-between">

                {{-- CHANGED: text-sm instead of text-base --}}
                <div class="min-w-0 flex items-center gap-3">
                    <div class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $farmer->name }}</div>

                    {{-- CHANGED: Smaller badges (text-[11px], px-1.5, py-0) --}}
                    <div
                        class="hidden sm:flex items-center gap-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        <span
                            class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-600 dark:text-slate-300">{{
                            $farmer->farmer_id }}</span>
                        <span class="truncate">{{ $farmer->route?->name ?? 'Unassigned' }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 sm:justify-end sm:shrink-0 mt-2 sm:mt-0">
                    @if($collected)
                    {{-- CHANGED: Smaller status badge --}}
                    <span
                        class="inline-flex items-center whitespace-nowrap rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ number_format($collection->qty, 2) }} L
                    </span>
                    @else
                    {{-- CHANGED: Smaller pending badge --}}
                    <span
                        class="inline-flex items-center whitespace-nowrap rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">Not
                        collected</span>
                    @endif

                    {{-- CHANGED: Smaller button (px-3 py-1 text-xs) --}}
                    <button type="button" @click="openModal(@js($payload))"
                        class="shrink-0 rounded-md bg-emerald-500 px-3 py-1 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 dark:focus:ring-offset-slate-900">{{
                        $buttonLabel }}</button>
                </div>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-slate-300 py-8 text-center dark:border-slate-700">
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">No farmers found matching your
                    search.</p>
            </div>
            @endforelse
        </div>
        <div class="mt-4">{{ $farmers->links() }}</div>
    </div>
    @endif

    {{-- Toast --}}
    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 left-1/2 z-[60] flex -translate-x-1/2 items-center gap-2 rounded-full bg-slate-900 px-6 py-3 text-sm font-bold text-white shadow-xl dark:bg-emerald-600 dark:text-white">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="toast.message"></span>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @click.self="closeModal()">
        <div x-show="modalOpen" x-transition.scale.90
            class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
            <div class="border-b border-slate-100 bg-slate-50/50 p-6 dark:border-slate-700 dark:bg-slate-800/50">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white"
                            x-text="form.edit ? 'Update Collection' : 'Collect Milk'"></h2>
                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400" x-text="form.name"></p>
                        <div
                            class="mt-2 inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ \Carbon\Carbon::parse($collectionDate)->isToday() ? 'Recording for Today' : 'Recording
                            for ' . \Carbon\Carbon::parse($collectionDate)->format('D, M j, Y') }}
                        </div>
                    </div>
                    <button type="button" @click="closeModal()"
                        class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-200"><svg
                            class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-5">
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-300">Quantity
                        (Liters)</label>
                    <div class="relative">
                        <input x-model="form.qty" type="number" step="0.01" min="0.1" placeholder="e.g. 15.5"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-lg font-bold text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500" />
                        <span
                            class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">L</span>
                    </div>
                    <p x-show="errors.qty" x-text="errors.qty" class="mt-2 text-sm font-semibold text-rose-500"></p>
                    @error('qty')<p class="mt-2 text-sm font-semibold text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-300">Note
                        (Optional)</label>
                    <textarea x-model="form.notes" rows="2" placeholder="Any corrections or comments..."
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500"></textarea>
                    <p x-show="errors.notes" x-text="errors.notes" class="mt-2 text-sm font-semibold text-rose-500"></p>
                    @error('notes')<p class="mt-2 text-sm font-semibold text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="closeModal()"
                        class="rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">Cancel</button>
                    <button type="submit" :disabled="saving"
                        class="rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!saving">Save Record</span>
                        <span x-show="saving" class="flex items-center gap-2"><svg class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>