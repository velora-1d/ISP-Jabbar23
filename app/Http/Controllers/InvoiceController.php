<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view invoices')->only(['index', 'show']);
        $this->middleware('permission:create invoices')->only(['create', 'store', 'generate']);
        $this->middleware('permission:edit invoices')->only(['edit', 'update']);
        $this->middleware('permission:delete invoices')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Invoice::with('customer');

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cid', 'like', "%{$search}%");
            })->orWhere('invoice_number', 'like', "%{$search}%");
        }

        $invoices = $query->latest()->paginate(15);

        $stats = [
            'unpaid' => Invoice::where('status', 'unpaid')->sum('amount'),
            'paid' => Invoice::where('status', 'paid')->whereMonth('updated_at', now()->month)->sum('amount'),
            'overdue' => Invoice::where('status', 'overdue')->count(),
        ];

        return view('invoices.index', compact('invoices', 'stats'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'payments.processedBy']);
        return view('invoices.show', compact('invoice'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        return view('invoices.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'due_date' => 'required|date|after_or_equal:period_start',
            'amount' => 'required|numeric|min:0',
            'items' => 'nullable|array', // Logic item detail nanti
        ]);

        $customer = Customer::find($validated['customer_id']);
        
        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'customer_id' => $customer->id,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'status' => 'unpaid',
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice berhasil dibuat manually.');
    }

    /**
     * Generate bulk invoices for active customers (Trigger artisan command).
     */
    public function generate()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('billing:generate');
            return back()->with('success', 'Proses generate invoice berjalan! Cek daftar invoice.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal generate invoice: ' . $e->getMessage());
        }
    }
}
