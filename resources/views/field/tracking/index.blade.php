@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-teal-400 to-cyan-400 bg-clip-text text-transparent">GPS Tracking</h1>
            <p class="text-gray-400 mt-1">Pantau lokasi teknisi secara real-time</p>
        </div>
    </div>

    <!-- Map Container -->
    <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
        <div id="map" class="w-full h-96 rounded-xl bg-gray-700 flex items-center justify-center">
            <div class="text-center text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-lg">Peta GPS Tracking</p>
                <p class="text-sm mt-2">Integrasi dengan Google Maps / Leaflet</p>
            </div>
        </div>
    </div>

    <!-- Technicians List -->
    <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 overflow-hidden">
        <div class="p-4 border-b border-gray-700/50">
            <h3 class="text-lg font-semibold text-white">Teknisi Aktif</h3>
        </div>
        <div class="divide-y divide-gray-700/50">
            @forelse($technicians as $tech)
            <div class="p-4 hover:bg-gray-700/30 transition-colors flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center">
                        <span class="text-white font-bold">{{ strtoupper(substr($tech->name, 0, 2)) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-white">{{ $tech->name }}</p>
                        <p class="text-sm text-gray-400">{{ $tech->phone ?? 'No phone' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $tech->is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-500/20 text-gray-400' }}">
                        {{ $tech->is_active ? 'Online' : 'Offline' }}
                    </span>
                    <button class="p-2 bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 rounded-lg transition-colors" title="Locate">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-400">Tidak ada teknisi aktif.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
