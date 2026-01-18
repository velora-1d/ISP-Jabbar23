<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreditNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view invoices')->only(['index', 'show']);
        $this->middleware('permission:create invoices')->only(['create', 'store']);
        $this->middleware('permission:edit invoices')->only(['apply', 'cancel']);
    }

    public function index(Request $request)
    {
        $query = CreditNote::with('customer');

        if ($request->filled('status')) {
            $query->where('status', '=', $request->status, 'and');
        }

        $creditNotes = $query->latest()->paginate(15, ['*']);

        // Stats
        $pendingCount = CreditNote::where('status', '=', 'pending', 'and')->count(['*']);
        $pendingValue = CreditNote::where('status', '=', 'pending', 'and')->sum('amount');
        $appliedCount = CreditNote::where('status', '=', 'applied', 'and')->count(['*']);
        $appliedValue = CreditNote::where('status', '=', 'applied', 'and')->sum('amount');

        $stats = [
            'pending_count' => $pendingCount,
            'pending_value' => $pendingValue,
            'applied_count' => $appliedCount,
            'applied_value' => $appliedValue,
        ];

        return view('billing.credit-notes.index', compact('creditNotes', 'stats'));
    }

    public function create()
    {
        $customers = Customer::where('status', '=', 'active', 'and')->get(['*']);
        return view('billing.credit-notes.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|in:overpayment,refund,discount,adjustment,other',
            'notes' => 'nullable|string|max:500',
        ]);

        $creditNumber = 'CN-' . now()->format('Ym') . '-' . strtoupper(Str::random(5));

        CreditNote::create([
            'credit_number' => $creditNumber,
            'customer_id' => $validated['customer_id'],
            'amount' => $validated['amount'],
            'issue_date' => now(),
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        return redirect()->route('billing.credit-notes')
            ->with('success', 'Credit Note berhasil dibuat!');
    }

    public function show(CreditNote $creditNote)
    {
        $creditNote->load(['customer', 'appliedInvoice']);
        
        // Get unpaid invoices for this customer
        $unpaidInvoices = Invoice::where('customer_id', '=', $creditNote->customer_id, 'and')
            ->where('status', '=', 'unpaid', 'and')
            ->get(['*']);

        return view('billing.credit-notes.show', compact('creditNote', 'unpaidInvoices'));
    }

    public function apply(Request $request, CreditNote $creditNote)
    {
        if ($creditNote->status !== 'pending') {
            return back()->with('error', 'Credit Note tidak dapat diterapkan!');
        }

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id'], ['*']);

        // Apply credit to invoice
        if ($creditNote->amount >= $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        }

        $creditNote->update([
            'status' => 'applied',
            'applied_to_invoice_id' => $invoice->id,
        ]);

        return back()->with('success', 'Credit Note berhasil diterapkan ke invoice!');
    }

    public function cancel(CreditNote $creditNote)
    {
        if ($creditNote->status !== 'pending') {
            return back()->with('error', 'Credit Note tidak dapat dibatalkan!');
        }

        $creditNote->update(['status' => 'cancelled']);

        return back()->with('success', 'Credit Note dibatalkan.');
    }
}
