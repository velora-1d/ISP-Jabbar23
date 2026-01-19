<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TechnicianController extends Controller
{
    /**
     * Display a listing of technicians with their real-time status.
     */
    public function index(): View
    {
        $technicians = User::role('noc')
            ->with(['currentTasks.package', 'currentTasks'])
            ->withCount(['currentTasks', 'completedCustomers', 'assignedCustomers'])
            ->orderBy('name')
            ->get();

        // Calculate stats based on computed status
        $stats = [
            'total' => $technicians->count(),
            'available' => $technicians->filter(fn($t) => $t->computed_status === 'available')->count(),
            'on_task' => $technicians->filter(fn($t) => $t->computed_status === 'on_task')->count(),
            'off_duty' => $technicians->filter(fn($t) => $t->computed_status === 'off_duty')->count(),
        ];

        return view('technicians.index', compact('technicians', 'stats'));
    }

    /**
     * Display the technician dashboard (My Dashboard).
     */
    public function dashboard(Request $request): View
    {
        /** @var User $technician */
        $technician = $request->user();
        
        // Eager load active tasks (survey, installing, etc)
        $activeTasks = $technician->currentTasks()
            ->with(['package'])
            ->orderBy('updated_at', 'desc')
            ->get();
            
        // Stats
        $stats = [
            'completed_today' => $technician->completedCustomers()->whereDate('updated_at', today())->count(),
            'pending_tasks' => $activeTasks->count(),
            'completed_month' => $technician->completedCustomers()->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
        ];

        return view('technicians.dashboard', compact('technician', 'activeTasks', 'stats'));
    }

    /**
     * Show the form for creating a new technician.
     */
    public function create(): View
    {
        return view('technicians.create');
    }

    /**
     * Store a newly created technician.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('technicians', 'public');
        }

        $user = User::create($validated);
        $user->assignRole('noc');

        return redirect()->route('technicians.index')
            ->with('success', 'Teknisi berhasil ditambahkan!');
    }

    /**
     * Display the specified technician with tracking info.
     */
    public function show(User $technician): View
    {
        $technician->load([
            'currentTasks.package',
            'assignedCustomers.package',
            'completedCustomers'
        ])->loadCount(['currentTasks', 'completedCustomers', 'assignedCustomers']);

        return view('technicians.show', compact('technician'));
    }

    /**
     * Show the form for editing the specified technician.
     */
    public function edit(User $technician): View
    {
        $technician->loadCount('currentTasks');
        return view('technicians.edit', compact('technician'));
    }

    /**
     * Update the specified technician.
     */
    public function update(Request $request, User $technician): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $technician->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($technician->photo) {
                Storage::disk('public')->delete($technician->photo);
            }
            $validated['photo'] = $request->file('photo')->store('technicians', 'public');
        }

        // Handle photo removal
        if ($request->has('remove_photo') && $request->remove_photo) {
            if ($technician->photo) {
                Storage::disk('public')->delete($technician->photo);
            }
            $validated['photo'] = null;
        }

        $validated['is_active'] = $request->has('is_active');

        $technician->update($validated);

        return redirect()->route('technicians.index')
            ->with('success', 'Data teknisi berhasil diupdate!');
    }

    /**
     * Remove the specified technician.
     */
    public function destroy(User $technician): RedirectResponse
    {
        // Delete photo if exists
        if ($technician->photo) {
            Storage::disk('public')->delete($technician->photo);
        }

        $technician->removeRole('noc');
        $technician->delete();

        return redirect()->route('technicians.index')
            ->with('success', 'Teknisi berhasil dihapus!');
    }

    /**
     * Toggle technician active status (off duty).
     */
    public function toggleActive(User $technician): RedirectResponse
    {
        $technician->update([
            'is_active' => !$technician->is_active
        ]);

        $status = $technician->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Teknisi berhasil {$status}!");
    }
}
