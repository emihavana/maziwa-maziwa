<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Route;
use App\Models\Farmer;
use App\Models\MilkCollection;
use Illuminate\Support\Carbon;
use Filament\Facades\Filament;

class CollectorDashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filter and Tab state
    public $selectedRouteId = null;
    public $searchQuery = '';
    public $activeTab = 'all'; // 'all', 'collected', 'pending'
    public $activeSection = 'overview'; // 'overview', 'farmers'
    public $darkMode = false;

    // ✅ NEW: the date the dashboard is "looking at".
    // - Collectors: always today (enforced on the server, not just in the UI).
    // - Admins: whatever they pick in the date input; defaults to today.
    // Kept as a string (Y-m-d) so wire:model.live round-trips cleanly.
    public string $collectionDate;

    // ALPINE: modal state ($showModal, $selectedFarmerId, $selectedFarmerName,
    // $isEditMode, $selectedFarmerRouteId) was REMOVED from here. The modal's data now
    // lives in the browser (Alpine `form`), so opening/closing makes no server request.
    // Livewire only needs these two properties so validate() and @error keep working.
    public $qty = '';
    public $notes = '';

    public function mount()
    {
        // Load default route if present
        $firstRoute = Route::first();
        $this->selectedRouteId = session('selected_route_id', $firstRoute?->id);

        // ✅ NEW: everyone starts on today. Collectors will stay here (enforced below).
        $this->collectionDate = Carbon::today()->toDateString();
    }

    // ✅ NEW: fires automatically when the date input changes (wire:model.live).
    // Security: collectors can't change the date — snap them back to today.
    public function updatedCollectionDate($value): void
    {
        if (! auth()->user()?->isAdmin()) {
            $this->collectionDate = Carbon::today()->toDateString();
            return;
        }

        // Validate admins actually sent a real, non-future date.
        // (Server-side mirror of the `max` attribute on the input.)
        $this->validate([
            'collectionDate' => ['required', 'date', 'before_or_equal:today'],
        ]);

        // ✅ NEW: tell Alpine to close the modal (its captured payload belongs to the old date).
        // Kept page reset so pagination doesn't point past the new result set.
        $this->resetPage();
        $this->dispatch('date-changed');
    }

    public function selectRoute($routeId)
    {
        $this->selectedRouteId = $routeId;
        session(['selected_route_id' => $routeId]);
    }

    public function selectSection(string $section)
    {
        $this->activeSection = $section;
    }

    // ALPINE: openCollectModal() and resetModal() were DELETED. Alpine opens/closes the modal
    // and fills it from data already on the page, so no server call is needed for either.

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function updatedSelectedRouteId()
    {
        $this->resetPage();
    }

    public function logout()
    {
        auth()->guard('web')->logout(); 

        session()->invalidate();
        session()->regenerateToken();

        return redirect(Filament::getLoginUrl());
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function toggleDarkMode()
    {
        $this->darkMode = ! $this->darkMode;
    }

    // ALPINE: this is now the ONLY request the modal makes. Alpine's save() calls it with the
    // farmer id and form values as arguments, so Livewire no longer has to remember which
    // farmer was selected between requests.
    public function saveCollection(string $farmerId, $qty, $notes = null)
    {
        $this->resetValidation();

        // Copy the values onto the public properties so validate() and @error work
        $this->qty = $qty;
        $this->notes = $notes;

        $this->validate([
            'qty' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string|max:100',
        ]);

        // 404 instead of a foreign-key error if the farmer_id is wrong
        $farmer = Farmer::where('farmer_id', $farmerId)->firstOrFail();

        // ✅ GUARD: the ONE place that decides which date gets written.
        // Collectors: always today, no matter what the browser sends.
        // Admins: the date they picked (already validated as non-future in updatedCollectionDate).
        // The UI is cosmetic — this is the actual rule.
        $date = auth()->user()?->isAdmin()
            ? $this->collectionDate
            : Carbon::today()->toDateString();

        // Edit mode is decided on the server (never trust a flag sent from the browser).
        // ✅ CHANGED: check for an existing collection on the chosen date, not just today.
        $isEdit = MilkCollection::where('farmer_id', $farmer->farmer_id)
            ->where('collection_date', $date)
            ->exists();

        MilkCollection::updateOrCreate(
            [
                'farmer_id' => $farmer->farmer_id,
                // ✅ CHANGED: use $date instead of Carbon::today()
                'collection_date' => $date,
            ],
            [
                'qty' => $this->qty,
                'collector_id' => auth()->id(),
                'correction_reason' => $this->notes,
                'is_correction' => $isEdit,
                'corrected_by' => $isEdit ? auth()->id() : null,
            ]
        );

        $this->reset(['qty', 'notes']);

        // ALPINE: 'collection-saved' makes Alpine close the modal (@collection-saved.window).
        // If validation fails above, we never reach this line, so the modal stays open.
        $this->dispatch('collection-saved');

        // ALPINE: 'notify' makes Alpine show the toast, which hides itself after 3 seconds.
        // ✅ CHANGED: mention the date so admins back-dating aren't surprised.
        $this->dispatch('notify', message: 'Milk entry saved for '
            . Carbon::parse($date)->format('M j, Y') . '!');
    }

    // Decides whether the "Back to Admin" button is shown.
    // Pick the line that matches how your app marks admins.
    // Redirect to the admin panel if the user is an admin
    public function backToAdmin()
    {
        if (auth()->user()?->isAdmin()) {
            return redirect(Filament::getPanel('admin')->getUrl());
        }
    }

    public function render()
    {
        // ✅ CHANGED: the whole page now reads from $date, not just today.
        // For collectors this is still today — but only the server decides that.
        $date = auth()->user()?->isAdmin()
            ? $this->collectionDate
            : Carbon::today()->toDateString();

        $routes = Route::all();

        $farmerQuery = Farmer::query()
            ->when($this->selectedRouteId, fn($q) => $q->where('route_id', $this->selectedRouteId))
            ->when($this->searchQuery, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('farmer_id', 'like', "%{$this->searchQuery}%");
            }))
            // ✅ CHANGED: tab filters use $date instead of $today
            ->when($this->activeTab === 'collected', fn($q) => $q->whereHas('milkCollections', fn($q) => $q->where('collection_date', $date)))
            ->when($this->activeTab === 'pending', fn($q) => $q->whereDoesntHave('milkCollections', fn($q) => $q->where('collection_date', $date)))
            ->with([
                // ✅ CHANGED: eager-load the collection for $date so the modal payload
                // (qty/notes/edit flag) matches the date being viewed.
                'milkCollections' => fn($q) => $q->where('collection_date', $date),
                'route',
            ]);

        $farmers = $farmerQuery->paginate(30);

        $selectedRoute = $this->selectedRouteId ? $routes->firstWhere('id', $this->selectedRouteId) : null;
        $selectedRouteName = $selectedRoute?->name ?? 'All Routes';

        // ✅ CHANGED: all counts below use $date instead of $today so admins see
        // the numbers for the day they picked.
        $totalFarmersRoute = $this->selectedRouteId
            ? Farmer::where('route_id', $this->selectedRouteId)->count()
            : Farmer::count();

        $dateFarmersRoute = Farmer::when($this->selectedRouteId, fn($q) => $q->where('route_id', $this->selectedRouteId))
            ->whereHas('milkCollections', fn($q) => $q->where('collection_date', $date))
            ->count();

        $remainingFarmersRoute = max(0, $totalFarmersRoute - $dateFarmersRoute);

        // ✅ CHANGED: sum for $date. For admins, DON'T filter by collector_id — they're
        // viewing the whole day's picture, not just their own entries.
        // (Collectors keep the same "my collections" behaviour as before.)
        $totalCollectedQty = MilkCollection::where('collection_date', $date)
            ->when(! auth()->user()?->isAdmin(), fn($q) => $q->where('collector_id', auth()->id()))
            ->sum('qty');

        $collectedCount = $dateFarmersRoute;
        $pendingCount = $totalFarmersRoute - $dateFarmersRoute;

        return view('livewire.collector-dashboard', [
            'routes' => $routes,
            'farmers' => $farmers,
            'totalCollectedQty' => $totalCollectedQty,
            'collectedCount' => $collectedCount,
            'pendingCount' => $pendingCount,
            'selectedRouteName' => $selectedRouteName,
            'totalFarmersRoute' => $totalFarmersRoute,
            'todayFarmersRoute' => $dateFarmersRoute, // ✅ CHANGED: name kept for view compatibility
            'remainingFarmersRoute' => $remainingFarmersRoute,
            'adminUrl' => Filament::getPanel('admin')->getUrl(),
            // ✅ NEW: exposed to the view so the "Showing collections for…" line
            // and the modal's date reminder render correctly.
            'collectionDate' => $date,
        ]);
    }
}