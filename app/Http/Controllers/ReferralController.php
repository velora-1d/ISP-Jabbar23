<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\Customer;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|sales');
    }

    public function index()
    {
        $referrals = Referral::with(['referrer', 'referred'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Referral::count(),
            'pending' => Referral::where('status', 'pending')->count(),
            'qualified' => Referral::where('status', 'qualified')->count(),
            'total_rewards' => Referral::where('reward_paid', true)->sum('reward_amount'),
        ];

        return view('marketing.referrals.index', compact('referrals', 'stats'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        return view('marketing.referrals.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'referrer_id' => 'required|exists:customers,id',
            'reward_amount' => 'required|numeric|min:0',
        ]);

        Referral::create($validated);

        return redirect()->route('referrals.index')
            ->with('success', 'Kode referral berhasil dibuat!');
    }

    public function markQualified(Referral $referral)
    {
        $referral->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);

        return back()->with('success', 'Referral dikualifikasi!');
    }

    public function payReward(Referral $referral)
    {
        $referral->update([
            'status' => 'rewarded',
            'reward_paid' => true,
            'rewarded_at' => now(),
        ]);

        return back()->with('success', 'Reward berhasil dibayarkan!');
    }

    public function destroy(Referral $referral)
    {
        $referral->delete();
        return redirect()->route('referrals.index')
            ->with('success', 'Referral berhasil dihapus!');
    }
}
