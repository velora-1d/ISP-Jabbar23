<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|noc');
    }

    public function index()
    {
        $technicians = User::role('Teknisi Lapangan')
            ->where('is_active', true)
            ->get();

        return view('field.tracking.index', compact('technicians'));
    }

    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // In production, store this in a tracking_locations table
        // For now, just update user metadata
        auth()->user()->update([
            'last_latitude' => $validated['latitude'],
            'last_longitude' => $validated['longitude'],
            'last_location_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
