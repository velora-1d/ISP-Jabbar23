@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-violet-400 to-purple-400 bg-clip-text text-transparent">
                Audit Logs
            </h1>
            <p class="text-gray-400 mt-1">Riwayat aktivitas sistem dan pengguna</p>
        </div>
        <form action="{{ route('audit-logs.clear') }}" method="POST" onsubmit="return confirm('Hapus log lebih dari 90 hari?')">
            @csrf
            @method('DELETE')
            <input type="hidden" name="days" value="90">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600/20 hover:bg-red-600/40 text-red-400 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear Old Logs
            </button>
        </form>
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
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Total Hari Ini</p>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total_today']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Creates</p>
                    <p class="text-2xl font-bold text-emerald-400">{{ $stats['creates'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Updates</p>
                    <p class="text-2xl font-bold text-blue-400">{{ $stats['updates'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-red-500 to-rose-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Deletes</p>
                    <p class="text-2xl font-bold text-red-400">{{ $stats['deletes'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-teal-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Logins</p>
                    <p class="text-2xl font-bold text-green-400">{{ $stats['logins'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 p-6">
        <form action="{{ route('audit-logs.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Action</label>
                <select name="action" class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-violet-500">
                    <option value="">Semua</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">User</label>
                <select name="user_id" class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-violet-500">
                    <option value="">Semua</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Model</label>
                <input type="text" name="model_type" value="{{ request('model_type') }}" placeholder="Customer, Invoice..." class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Dari</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Sampai</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-3 py-2 text-white text-sm focus:ring-2 focus:ring-violet-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl transition-colors">Filter</button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl border border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Waktu</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">IP</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm text-white">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-xs">{{ strtoupper(substr($log->user_name ?? 'S', 0, 2)) }}</span>
                                </div>
                                <span class="text-white text-sm">{{ $log->user_name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-lg text-xs font-medium bg-{{ $log->action_color }}-500/20 text-{{ $log->action_color }}-400">
                                {{ ucfirst($log->action) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-300 text-sm">
                            {{ $log->model_name }}
                            @if($log->model_id)
                            <span class="text-gray-500">#{{ $log->model_id }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-300 text-sm max-w-xs truncate">
                            {{ $log->description }}
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-sm font-mono">
                            {{ $log->ip_address }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('audit-logs.show', $log) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-violet-600/20 hover:bg-violet-600/40 text-violet-400 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>Belum ada log aktivitas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-700/50">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
