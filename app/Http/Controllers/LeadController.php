<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Package;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with(['interestedPackage', 'assignedTo']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Filter by assigned user
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('lead_number', 'like', "%{$search}%");
            });
        }

        // Stats
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', 'new')->count(),
            'in_progress' => Lead::whereIn('status', ['contacted', 'qualified', 'proposal', 'negotiation'])->count(),
            'won' => Lead::where('status', 'won')->count(),
            'lost' => Lead::where('status', 'lost')->count(),
        ];

        $leads = $query->latest()->paginate(15);
        $salesUsers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Sales & CS']))->get();

        return view('leads.index', compact('leads', 'stats', 'salesUsers'));
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->get();
        $salesUsers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Sales & CS']))->get();

        return view('leads.create', compact('packages', 'salesUsers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'rt_rw' => 'nullable|string|max:20',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'source' => 'required|in:website,whatsapp,referral,walk-in,social_media,other',
            'interested_package_id' => 'nullable|exists:packages,id',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        Lead::create($validated);

        return redirect()->route('leads.index')->with('success', 'Lead berhasil ditambahkan!');
    }

    public function show(Lead $lead)
    {
        $lead->load(['interestedPackage', 'assignedTo', 'customer']);
        return view('leads.show', compact('lead'));
    }

    public function edit(Lead $lead)
    {
        $packages = Package::where('is_active', true)->get();
        $salesUsers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Sales & CS']))->get();

        return view('leads.edit', compact('lead', 'packages', 'salesUsers'));
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'rt_rw' => 'nullable|string|max:20',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'source' => 'required|in:website,whatsapp,referral,walk-in,social_media,other',
            'interested_package_id' => 'nullable|exists:packages,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'notes' => 'nullable|string',
        ]);

        $lead->update($validated);

        return redirect()->route('leads.index')->with('success', 'Lead berhasil diupdate!');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus!');
    }

    /**
     * Convert lead to customer
     */
    public function convert(Lead $lead)
    {
        if ($lead->status === 'won' && $lead->customer_id) {
            return redirect()->route('leads.show', $lead)->with('error', 'Lead sudah dikonversi!');
        }

        // Create customer from lead data
        $customer = Customer::create([
            'name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'address' => $lead->address,
            'rt_rw' => $lead->rt_rw,
            'kelurahan' => $lead->kelurahan,
            'kecamatan' => $lead->kecamatan,
            'kabupaten' => $lead->kabupaten,
            'provinsi' => $lead->provinsi,
            'kode_pos' => $lead->kode_pos,
            'latitude' => $lead->latitude,
            'longitude' => $lead->longitude,
            'package_id' => $lead->interested_package_id,
            'assigned_to' => $lead->assigned_to,
            'status' => Customer::STATUS_REGISTERED,
            'notes' => "Converted from Lead: {$lead->lead_number}",
        ]);

        // Update lead
        $lead->update([
            'status' => 'won',
            'converted_at' => now(),
            'customer_id' => $customer->id,
        ]);

        return redirect()->route('customers.show', $customer)->with('success', 'Lead berhasil dikonversi menjadi Customer!');
    }
}
