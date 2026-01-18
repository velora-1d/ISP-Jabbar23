<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PackageController extends Controller
{
    /**
     * Display a listing of packages.
     */
    public function index(): View
    {
        $packages = Package::latest()->get();
        return view('packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new package.
     */
    public function create(): View
    {
        return view('packages.create');
    }

    /**
     * Store a newly created package.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed_down' => 'required|integer|min:1',
            'speed_up' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Package::create($validated);

        return redirect()->route('packages.index')
            ->with('success', 'Paket internet berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified package.
     */
    public function edit(Package $package): View
    {
        return view('packages.edit', compact('package'));
    }

    /**
     * Update the specified package.
     */
    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed_down' => 'required|integer|min:1',
            'speed_up' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $package->update($validated);

        return redirect()->route('packages.index')
            ->with('success', 'Paket internet berhasil diupdate!');
    }

    /**
     * Remove the specified package.
     */
    public function destroy(Package $package): RedirectResponse
    {
        $package->delete();

        return redirect()->route('packages.index')
            ->with('success', 'Paket internet berhasil dihapus!');
    }
}
