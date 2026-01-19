<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class SlaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|noc');
    }

    public function index()
    {
        $policies = SlaPolicy::orderBy('priority', 'desc')->get();
        
        $stats = [
            'total' => SlaPolicy::count(),
            'active' => SlaPolicy::where('is_active', true)->count(),
        ];

        return view('support.sla.index', compact('policies', 'stats'));
    }

    public function create()
    {
        return view('support.sla.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'first_response_hours' => 'required|integer|min:1',
            'resolution_hours' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        SlaPolicy::create($validated);

        return redirect()->route('sla.index')
            ->with('success', 'SLA Policy berhasil dibuat!');
    }

    public function edit(SlaPolicy $sla)
    {
        return view('support.sla.edit', compact('sla'));
    }

    public function update(Request $request, SlaPolicy $sla)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'first_response_hours' => 'required|integer|min:1',
            'resolution_hours' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $sla->update($validated);

        return redirect()->route('sla.index')
            ->with('success', 'SLA Policy berhasil diperbarui!');
    }

    public function destroy(SlaPolicy $sla)
    {
        $sla->delete();
        return redirect()->route('sla.index')
            ->with('success', 'SLA Policy berhasil dihapus!');
    }
}
