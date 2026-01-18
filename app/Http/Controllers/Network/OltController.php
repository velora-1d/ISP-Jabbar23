<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\Olt;
use Illuminate\Http\Request;

class OltController extends Controller
{
    public function index(Request $request)
    {
        $query = Olt::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('ip_address', 'like', "%{$request->search}%");
        }

        $olts = $query->latest()->paginate(10);
        return view('network.olts.index', compact('olts'));
    }

    public function create()
    {
        return view('network.olts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:olts,name|max:50',
            'ip_address' => 'nullable|ipv4',
            'brand' => 'nullable|string|max:50',
            'type' => 'required|in:EPON,GPON,XGPON',
            'total_pon_ports' => 'required|integer|min:1',
            'status' => 'required|in:active,offline,maintenance',
            'location' => 'nullable|string|max:255',
        ]);

        Olt::create($validated);

        return redirect()->route('network.olts.index')->with('success', 'OLT created successfully.');
    }

    public function edit(Olt $olt)
    {
        return view('network.olts.edit', compact('olt'));
    }

    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|max:50|unique:olts,name,' . $olt->id,
            'ip_address' => 'nullable|ipv4',
            'brand' => 'nullable|string|max:50',
            'type' => 'required|in:EPON,GPON,XGPON',
            'total_pon_ports' => 'required|integer|min:1',
            'status' => 'required|in:active,offline,maintenance',
            'location' => 'nullable|string|max:255',
        ]);

        $olt->update($validated);

        return redirect()->route('network.olts.index')->with('success', 'OLT updated successfully.');
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();
        return redirect()->route('network.olts.index')->with('success', 'OLT deleted successfully.');
    }
}
