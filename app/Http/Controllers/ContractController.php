<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('contract_number', 'like', "%{$search}%");
        }

        $contracts = $query->latest()->paginate(15);
        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        $customers = Customer::all();
        return view('contracts.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contract_number' => 'required|string|unique:contracts,contract_number',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,expired,terminated,draft',
            'terms' => 'nullable|string',
            'scanned_copy' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        if ($request->hasFile('scanned_copy')) {
            $path = $request->file('scanned_copy')->store('contracts', 'public');
            $validated['scanned_copy_path'] = $path;
        }

        Contract::create($validated);

        return redirect()->route('contracts.index')->with('success', 'Kontrak berhasil dibuat!');
    }

    public function show(Contract $contract)
    {
        $contract->load('customer');
        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $customers = Customer::all();
        return view('contracts.edit', compact('contract', 'customers'));
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contract_number' => 'required|string|unique:contracts,contract_number,' . $contract->id,
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,expired,terminated,draft',
            'terms' => 'nullable|string',
            'scanned_copy' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        if ($request->hasFile('scanned_copy')) {
            $path = $request->file('scanned_copy')->store('contracts', 'public');
            $validated['scanned_copy_path'] = $path;
        }

        $contract->update($validated);

        return redirect()->route('contracts.index')->with('success', 'Kontrak berhasil diupdate!');
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('contracts.index')->with('success', 'Kontrak berhasil dihapus!');
    }
}
