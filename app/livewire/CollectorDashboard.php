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
        auth()->guard('web')->logout(); // swap 'web' if your Filament panel uses a custom guard

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

        // Edit mode is decided on the server (never trust a flag sent from the browser)
        $isEdit = MilkCollection::where('farmer_id', $farmer->farmer_id)
            ->where('collection_date', Carbon::today()->toDateString())
            ->exists();

        MilkCollection::updateOrCreate(
            [
                'farmer_id' => $farmer->farmer_id,
                'collection_date' => Carbon::today()->toDateString(),
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
        $this->dispatch('notify', message: 'Milk entry saved successfully!');
    }

    // Decides whether the "Back to Admin" button is shown.
    // Pick the line that matches how your app marks admins.
    protected function isAdmin(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Option A (active): the user is allowed into the Filament admin panel.
        // Warning: if collectors can also access the panel, they will see the button too.
        return $user->canAccessPanel(Filament::getPanel('admin'));

        // Option B: role column on users table
        // return (bool) $user->is_admin;

        // Option C: Spatie permissions
        // return $user->hasRole('admin');
    }

    public function render()
    {
        $today = Carbon::today()->toDateString();
        $routes = Route::all();

        $farmerQuery = Farmer::query()
            ->when($this->selectedRouteId, fn($q) => $q->where('route_id', $this->selectedRouteId))
            ->when($this->searchQuery, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('farmer_id', 'like', "%{$this->searchQuery}%");
            }))
            ->when($this->activeTab === 'collected', fn($q) => $q->whereHas('milkCollections', fn($q) => $q->where('collection_date', $today)))
            ->when($this->activeTab === 'pending', fn($q) => $q->whereDoesntHave('milkCollections', fn($q) => $q->where('collection_date', $today)))
            ->with([
                'milkCollections' => fn($q) => $q->where('collection_date', $today),
                'route',
            ]);

        $farmers = $farmerQuery->paginate(30);

        $selectedRoute = $this->selectedRouteId ? $routes->firstWhere('id', $this->selectedRouteId) : null;
        $selectedRouteName = $selectedRoute?->name ?? 'All Routes';

        $totalFarmersRoute = $this->selectedRouteId
            ? Farmer::where('route_id', $this->selectedRouteId)->count()
            : Farmer::count();

        $todayFarmersRoute = Farmer::when($this->selectedRouteId, fn($q) => $q->where('route_id', $this->selectedRouteId))
            ->whereHas('milkCollections', fn($q) => $q->where('collection_date', $today))
            ->count();

        $remainingFarmersRoute = max(0, $totalFarmersRoute - $todayFarmersRoute);

        $totalCollectedQty = MilkCollection::where('collection_date', $today)
            ->where('collector_id', auth()->id())
            ->sum('qty');

        $collectedCount = $todayFarmersRoute;
        $pendingCount = $totalFarmersRoute - $todayFarmersRoute;

        return view('livewire.collector-dashboard', [
            'routes' => $routes,
            'farmers' => $farmers,
            'totalCollectedQty' => $totalCollectedQty,
            'collectedCount' => $collectedCount,
            'pendingCount' => $pendingCount,
            'selectedRouteName' => $selectedRouteName,
            'totalFarmersRoute' => $totalFarmersRoute,
            'todayFarmersRoute' => $todayFarmersRoute,
            'remainingFarmersRoute' => $remainingFarmersRoute,
            'isAdmin' => $this->isAdmin(),
            'adminUrl' => Filament::getPanel('admin')->getUrl(),
        ]);
    }
}
