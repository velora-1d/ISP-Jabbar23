<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\Odp;
use Illuminate\Http\Request;

class OdpController extends Controller
{
    public function __construct()
    {
        // Simple permission check
        // $this->middleware('permission:manage network')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Odp::query();
        
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%");
        }

        $odps = $query->latest()->paginate(10);
        
        return view('network.odps.index', compact('odps'));
    }

    public function create()
    {
        return view('network.odps.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:odps,name|max:50',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,full',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Odp::create($validated);

        return redirect()->route('network.odps.index')->with('success', 'ODP created successfully.');
    }

    public function edit(Odp $odp)
    {
        return view('network.odps.edit', compact('odp'));
    }

    public function update(Request $request, Odp $odp)
    {
        $validated = $request->validate([
            'name' => 'required|max:50|unique:odps,name,' . $odp->id,
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|min:1',
            'status' => 'required|in:active,maintenance,full',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $odp->update($validated);

        return redirect()->route('network.odps.index')->with('success', 'ODP updated successfully.');
    }

    public function destroy(Odp $odp)
    {
        $odp->delete();
        return redirect()->route('network.odps.index')->with('success', 'ODP deleted successfully.');
    }
}
