<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Package;
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

        // Use paid_at column and confirmed status
        $totalRevenue = Payment::whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'confirmed')
            ->sum('amount');

        $revenueByMonth = Payment::where('status', 'confirmed')
            ->where('paid_at', '>=', Carbon::now()->subMonths(12))
            ->select(
                DB::raw('YEAR(paid_at) as year'),
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $revenueByMethod = Payment::where('status', 'confirmed')
            ->whereBetween('paid_at', [$startDate, $endDate])
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

        // Use Customer's package_id directly instead of Subscription model
        $customersByPackage = Customer::where('status', 'active')
            ->whereNotNull('package_id')
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

        // Top customers by payments
        $topCustomers = Customer::select('customers.*')
            ->leftJoin('payments', 'customers.id', '=', 'payments.customer_id')
            ->where('payments.status', 'confirmed')
            ->groupBy('customers.id')
            ->selectRaw('SUM(payments.amount) as total_paid')
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
        // ODP uses total_ports, calculate used by counting customers
        $odpStats = [
            'total' => \App\Models\Odp::count(),
            'active' => \App\Models\Odp::where('status', 'active')->count(),
            'full' => \App\Models\Odp::where('status', 'full')->count(),
            'maintenance' => \App\Models\Odp::where('status', 'maintenance')->count(),
        ];

        $oltStats = [
            'total' => \App\Models\Olt::count(),
            'online' => \App\Models\Olt::where('status', 'online')->count(),
            'offline' => \App\Models\Olt::where('status', 'offline')->count(),
        ];

        $routerStats = [];

        // Calculate bandwidth from packages
        $bandwidthUsage = [
            'total_allocated' => Customer::where('status', 'active')
                ->join('packages', 'customers.package_id', '=', 'packages.id')
                ->sum('packages.bandwidth'),
        ];

        return view('reports.network', compact('odpStats', 'oltStats', 'routerStats', 'bandwidthUsage'));
    }

    /**
     * Commission Reports - Using Partner model instead of Commission
     */
    public function commissions(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Get partners with their referred customers and calculate commissions
        $partners = \App\Models\Partner::withCount(['customers as total_customers'])
            ->get();

        // Calculate commission based on referred customers' payments
        $commissionData = [];
        $totalCommissions = 0;
        $paidCommissions = 0;
        $pendingCommissions = 0;

        foreach ($partners as $partner) {
            $customerPayments = Payment::whereHas('customer', function ($q) use ($partner) {
                    $q->where('partner_id', $partner->id);
                })
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount');
            
            $commission = $customerPayments * ($partner->commission_rate / 100);
            
            $commissionData[] = [
                'partner' => $partner,
                'amount' => $commission,
                'customer_payments' => $customerPayments,
            ];
            
            $totalCommissions += $commission;
        }

        // Top performers by customer count
        $topPerformers = \App\Models\Partner::withCount('customers')
            ->orderByDesc('customers_count')
            ->limit(10)
            ->get();

        return view('reports.commissions', compact(
            'commissionData', 'totalCommissions', 'paidCommissions', 'pendingCommissions',
            'topPerformers', 'startDate', 'endDate'
        ));
    }
}
