<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|sales');
    }

    public function index()
    {
        $campaigns = Campaign::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => Campaign::count(),
            'running' => Campaign::where('status', 'running')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
            'total_sent' => Campaign::sum('sent_count'),
        ];

        return view('marketing.campaigns.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        return view('marketing.campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:email,whatsapp,sms,push',
            'message_template' => 'required|string',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = $request->filled('scheduled_at') ? 'scheduled' : 'draft';

        Campaign::create($validated);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign berhasil dibuat!');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('creator');
        return view('marketing.campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        return view('marketing.campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:email,whatsapp,sms,push',
            'message_template' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign berhasil diperbarui!');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign berhasil dihapus!');
    }

    public function launch(Campaign $campaign)
    {
        $campaign->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // TODO: Dispatch job to send messages

        return back()->with('success', 'Campaign berhasil diluncurkan!');
    }

    public function cancel(Campaign $campaign)
    {
        $campaign->update(['status' => 'cancelled']);
        return back()->with('success', 'Campaign dibatalkan!');
    }
}
