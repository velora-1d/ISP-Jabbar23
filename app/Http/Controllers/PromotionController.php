<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|sales|finance');
    }

    public function index(Request $request)
    {
        $query = Promotion::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            } elseif ($request->status === 'expired') {
                $query->where('end_date', '<', now());
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $promotions = $query->paginate(20);

        $stats = [
            'total' => Promotion::count(),
            'active' => Promotion::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
            'total_usage' => Promotion::sum('usage_count'),
            'expiring_soon' => Promotion::where('is_active', true)
                ->whereBetween('end_date', [now(), now()->addDays(7)])
                ->count(),
        ];

        return view('marketing.promotions.index', compact('promotions', 'stats'));
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('marketing.promotions.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:promotions,code',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_month',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'applicable_packages' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Generate unique code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(Str::random(8));
        }

        $validated['is_active'] = $request->has('is_active');

        Promotion::create($validated);

        return redirect()->route('promotions.index')
            ->with('success', 'Promosi berhasil dibuat!');
    }

    public function show(Promotion $promotion)
    {
        return view('marketing.promotions.show', compact('promotion'));
    }

    public function edit(Promotion $promotion)
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('marketing.promotions.edit', compact('promotion', 'packages'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:promotions,code,' . $promotion->id,
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_month',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'applicable_packages' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $promotion->update($validated);

        return redirect()->route('promotions.index')
            ->with('success', 'Promosi berhasil diperbarui!');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return redirect()->route('promotions.index')
            ->with('success', 'Promosi berhasil dihapus!');
    }

    public function toggleActive(Promotion $promotion)
    {
        $promotion->update(['is_active' => !$promotion->is_active]);
        return back()->with('success', 'Status promosi berhasil diubah!');
    }
}
