<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Route;
use App\Models\Farmer;
use App\Models\MilkCollection;
use Illuminate\Support\Carbon;
use Filament\Facades\Filament; // CHANGED: imported so we can build the admin URL and check panel access

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

    public $selectedFarmerRouteId = null;

    // Modal Form State
    // CHANGED: removed `public $showModal = false;`
    // Alpine (modalOpen in the view) now owns whether the modal is open or closed.
    // Livewire only holds the form data.
    public $selectedFarmerId = null;
    public $selectedFarmerName = '';
    public $qty = '';
    public $notes = '';
    public $isEditMode = false;

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

    public function openCollectModal(string|int $farmerId)
    {
        $this->selectedFarmerId = (string) $farmerId;

        $farmer = Farmer::with(['milkCollections' => fn($q) => $q->where('collection_date', Carbon::today()->toDateString())])
            ->where('farmer_id', $this->selectedFarmerId)
            ->first();

        $this->selectedFarmerName = $farmer?->name ?? '';
        $this->selectedFarmerRouteId = $farmer?->route_id;
        $collection = $farmer?->milkCollections->first();
        $this->qty = $collection?->qty ?? '';
        $this->notes = $collection?->correction_reason ?? '';
        $this->isEditMode = $collection !== null;

        // CHANGED: removed `$this->showModal = true;`
        // Alpine opens the modal instantly on click; this method just loads the data.
    }

    // CHANGED: renamed from closeModal() to resetModal().
    // It no longer closes anything (Alpine does that). It only clears the form data.
    public function resetModal()
    {
        $this->reset(['selectedFarmerId', 'selectedFarmerName', 'qty', 'notes', 'isEditMode', 'selectedFarmerRouteId']);
        $this->resetValidation(); // CHANGED: also clear old validation errors so they don't show next time
    }

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

    public function saveCollection()
    {
        $this->validate([
            'qty' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string|max:100',
        ]);

        MilkCollection::updateOrCreate(
            [
                'farmer_id' => $this->selectedFarmerId,
                'collection_date' => Carbon::today()->toDateString(),
            ],
            [
                'qty' => $this->qty,
                'collector_id' => auth()->id(),
                'correction_reason' => $this->notes,
                'is_correction' => (bool) $this->isEditMode,
                'corrected_by' => $this->isEditMode ? auth()->id() : null,
            ]
        );

        if ($this->selectedFarmerId !== null && $this->selectedFarmerRouteId) {
            Farmer::where('farmer_id', $this->selectedFarmerId)
                ->update(['route_id' => $this->selectedFarmerRouteId]);
        }

        // CHANGED: was $this->closeModal(). Now we clear the form data, then
        // dispatch a browser event that the view listens for to close the modal.
        // If validation fails above, we never reach this line, so the modal stays open.
        $this->resetModal();
        $this->dispatch('collection-saved');

        // CHANGED: was session()->flash('message', ...), which the view never displayed.
        // Now we dispatch a 'notify' browser event; the Alpine toast in the view shows it
        // and hides it automatically after 3 seconds.
        $this->dispatch('notify', message: 'Milk entry saved successfully!');
    }

    // CHANGED: new method. Decides whether the "Back to Admin" button is shown.
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
            // CHANGED: two new values used by the "Back to Admin" button
            'isAdmin' => $this->isAdmin(),
            'adminUrl' => Filament::getPanel('admin')->getUrl(),
        ]);
    }
}
