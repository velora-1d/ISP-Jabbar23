@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-green-400 to-emerald-400 bg-clip-text text-transparent">
                Customer Messages
            </h1>
            <p class="text-gray-400 mt-1">Komunikasi dengan pelanggan via WhatsApp, SMS, Email</p>
        </div>
        <a href="{{ route('messages.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-green-500/25">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Kirim Pesan
        </a>
    </div>

    @if(session('success'))
    <div class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-6 py-4 rounded-2xl flex items-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Total Pesan</p>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Hari Ini</p>
                    <p class="text-2xl font-bold text-blue-400">{{ $stats['today'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-cyan-500 to-teal-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Masuk (Hari Ini)</p>
                    <p class="text-2xl font-bold text-cyan-400">{{ $stats['inbound'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Keluar (Hari Ini)</p>
                    <p class="text-2xl font-bold text-purple-400">{{ $stats['outbound'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-4">
        <form action="{{ route('messages.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select name="customer_id" class="bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Pelanggan</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
            <select name="channel" class="bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Channel</option>
                <option value="whatsapp" {{ request('channel') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                <option value="sms" {{ request('channel') == 'sms' ? 'selected' : '' }}>SMS</option>
                <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>Email</option>
                <option value="web" {{ request('channel') == 'web' ? 'selected' : '' }}>Web</option>
            </select>
            <select name="direction" class="bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Arah</option>
                <option value="inbound" {{ request('direction') == 'inbound' ? 'selected' : '' }}>Masuk</option>
                <option value="outbound" {{ request('direction') == 'outbound' ? 'selected' : '' }}>Keluar</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-xl transition-colors">Filter</button>
        </form>
    </div>

    <!-- Messages Table -->
    <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Channel</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Pesan</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($messages as $message)
                    <tr class="hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($message->customer->name ?? 'C', 0, 2)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-white">{{ $message->customer->name ?? 'Unknown' }}</p>
                                    <p class="text-sm text-gray-400">{{ $message->customer->phone ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($message->direction == 'inbound')
                                <span class="text-cyan-400">↓</span>
                                @else
                                <span class="text-purple-400">↑</span>
                                @endif
                                <span class="px-2 py-1 rounded-lg text-xs font-medium bg-gray-700 text-gray-300 capitalize">{{ $message->channel }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-300 max-w-md truncate">
                            {{ Str::limit($message->content, 80) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-lg text-xs font-medium bg-{{ $message->status_color }}-500/20 text-{{ $message->status_color }}-400 capitalize">
                                {{ $message->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ $message->created_at->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <p>Belum ada pesan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-700/50">
            {{ $messages->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
