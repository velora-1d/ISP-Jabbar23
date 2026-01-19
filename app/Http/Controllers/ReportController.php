<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|finance');
    }

    /**
     * Display the financial report (legacy).
     */
    public function index(Request $request): View
    {
        $hasFilter = $request->has('month') || $request->has('year');
        
        $currentMonth = date('n');
        $currentYear = date('Y');

        $query = Invoice::with(['customer.package']);

        if ($request->filled('month')) {
            $query->whereMonth('due_date', $request->month);
        } elseif (!$hasFilter) {
            $request->merge(['month' => $currentMonth]);
            $query->whereMonth('due_date', $currentMonth);
        }

        if ($request->filled('year')) {
            $query->whereYear('due_date', $request->year);
        } elseif (!$hasFilter) {
            $request->merge(['year' => $currentYear]);
            $query->whereYear('due_date', $currentYear);
        }

        $totalRevenue = (clone $query)->where('status', 'paid')->sum('amount');
        $totalUnpaid = (clone $query)->where('status', 'unpaid')->sum('amount');
        $countPaid = (clone $query)->where('status', 'paid')->count();
        $countUnpaid = (clone $query)->where('status', 'unpaid')->count();
        $countTotal = $countPaid + $countUnpaid;

        $invoices = $query->latest('due_date')->get();

        $years = Invoice::selectRaw('YEAR(due_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($years->isEmpty()) {
            $years = collect([date('Y')]);
        }

        return view('reports.index', compact(
            'invoices', 'totalRevenue', 'totalUnpaid',
            'countPaid', 'countUnpaid', 'countTotal', 'years'
        ));
    }

    /**
     * Revenue Reports
     */
    public function revenue(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $totalRevenue = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'success')
            ->sum('amount');

        $revenueByMonth = Payment::where('status', 'success')
            ->where('payment_date', '>=', Carbon::now()->subMonths(12))
            ->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $revenueByMethod = Payment::where('status', 'success')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $pendingInvoices = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->sum('total');

        $paidCount = Invoice::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $unpaidCount = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return view('reports.revenue', compact(
            'totalRevenue', 'revenueByMonth', 'revenueByMethod',
            'pendingInvoices', 'paidCount', 'unpaidCount',
            'startDate', 'endDate'
        ));
    }

    /**
     * Customer Reports
     */
    public function customers(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        $churnedCustomers = Customer::where('status', 'inactive')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $customersByPackage = Subscription::where('status', 'active')
            ->select('package_id', DB::raw('COUNT(*) as total'))
            ->groupBy('package_id')
            ->with('package:id,name')
            ->get();

        $customerGrowth = Customer::where('created_at', '>=', Carbon::now()->subMonths(12))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $topCustomers = Customer::withSum(['payments as total_paid' => function ($q) {
            $q->where('status', 'success');
        }], 'amount')
            ->orderByDesc('total_paid')
            ->limit(10)
            ->get();

        return view('reports.customers', compact(
            'totalCustomers', 'activeCustomers', 'newCustomers', 'churnedCustomers',
            'customersByPackage', 'customerGrowth', 'topCustomers',
            'startDate', 'endDate'
        ));
    }

    /**
     * Network Reports
     */
    public function network(Request $request)
    {
        $odpStats = [
            'total' => \App\Models\Odp::count(),
            'available' => \App\Models\Odp::whereRaw('capacity > used_ports')->count(),
            'full' => \App\Models\Odp::whereRaw('capacity <= used_ports')->count(),
        ];

        $oltStats = [
            'total' => \App\Models\Olt::count(),
            'online' => \App\Models\Olt::where('status', 'online')->count(),
            'offline' => \App\Models\Olt::where('status', 'offline')->count(),
        ];

        $routerStats = [];
        if (class_exists(\App\Models\Router::class)) {
            $routerStats = [
                'total' => \App\Models\Router::count(),
                'online' => \App\Models\Router::where('status', 'online')->count(),
                'offline' => \App\Models\Router::where('status', 'offline')->count(),
            ];
        }

        $bandwidthUsage = [
            'total_allocated' => \App\Models\Customer::where('status', 'active')
                ->join('subscriptions', 'customers.id', '=', 'subscriptions.customer_id')
                ->join('packages', 'subscriptions.package_id', '=', 'packages.id')
                ->where('subscriptions.status', 'active')
                ->sum('packages.bandwidth'),
        ];

        return view('reports.network', compact('odpStats', 'oltStats', 'routerStats', 'bandwidthUsage'));
    }

    /**
     * Commission Reports
     */
    public function commissions(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $salesCommissions = \App\Models\Commission::whereBetween('created_at', [$startDate, $endDate])
            ->with(['partner', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCommissions = $salesCommissions->sum('amount');
        $paidCommissions = $salesCommissions->where('status', 'paid')->sum('amount');
        $pendingCommissions = $salesCommissions->where('status', 'pending')->sum('amount');

        $topPerformers = \App\Models\Commission::whereBetween('created_at', [$startDate, $endDate])
            ->select('partner_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('partner_id')
            ->with('partner:id,name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('reports.commissions', compact(
            'salesCommissions', 'totalCommissions', 'paidCommissions', 'pendingCommissions',
            'topPerformers', 'startDate', 'endDate'
        ));
    }
}
