<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the financial report.
     */
    public function index(Request $request): View
    {
        // Default to current month/year if not specified, OR show 'All' if specifically requested?
        // Usually reports default to "Current Month".
        // If parameters are missing, we can default them in the view or here.
        // Let's default to Empty (All Time) or Current Month?
        // User behavior: usually wants to see current status.
        // Let's check if request has 'month'. If not, we don't filter.
        // BUT for a "Report", showing "All Time" might be too much data.
        // Let's default to Current Month/Year for better UX.
        
        $hasFilter = $request->has('month') || $request->has('year');
        
        $currentMonth = date('n');
        $currentYear = date('Y');

        // Initial Query
        $query = Invoice::with(['customer.package']);

        if ($request->filled('month')) {
            $query->whereMonth('due_date', $request->month);
        } elseif (!$hasFilter) {
            // Default filter
            $request->merge(['month' => $currentMonth]);
            $query->whereMonth('due_date', $currentMonth);
        }

        if ($request->filled('year')) {
            $query->whereYear('due_date', $request->year);
        } elseif (!$hasFilter) {
            // Default filter
            $request->merge(['year' => $currentYear]);
            $query->whereYear('due_date', $currentYear);
        }

        // Calculations
        $totalRevenue = (clone $query)->where('status', 'paid')->sum('amount');
        $totalUnpaid = (clone $query)->where('status', 'unpaid')->sum('amount');
        $countPaid = (clone $query)->where('status', 'paid')->count();
        $countUnpaid = (clone $query)->where('status', 'unpaid')->count();
        $countTotal = $countPaid + $countUnpaid;

        // Get Data
        $invoices = $query->latest('due_date')->get();

        // Filter Options
        $years = Invoice::selectRaw('YEAR(due_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        return view('reports.index', compact(
            'invoices',
            'totalRevenue',
            'totalUnpaid',
            'countPaid',
            'countUnpaid',
            'countTotal',
            'years'
        ));
    }
}
