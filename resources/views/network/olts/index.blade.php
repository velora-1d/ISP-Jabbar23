@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-white">OLT Management</h2>
                    <a href="{{ route('network.olts.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                        + Add New OLT
                    </a>
                </div>

                <div class="mb-4">
                    <form action="{{ route('network.olts.index') }}" method="GET" class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Name or IP..." 
                            class="bg-gray-700 text-white rounded-lg px-4 py-2 border border-gray-600 focus:outline-none focus:border-indigo-500 w-full md:w-64">
                        <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg border border-gray-600">
                            Search
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-400">
                        <thead class="text-xs text-gray-400 uppercase bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">IP Address</th>
                                <th class="px-6 py-3">Type / Brand</th>
                                <th class="px-6 py-3">PON Ports</th>
                                <th class="px-6 py-3">Location</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($olts as $olt)
                            <tr class="border-b border-gray-700 hover:bg-gray-700/50">
                                <td class="px-6 py-4 font-medium text-white">
                                    {{ $olt->name }}
                                </td>
                                <td class="px-6 py-4 font-mono text-indigo-400">
                                    {{ $olt->ip_address ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $olt->type }} <span class="text-xs text-gray-500">({{ $olt->brand }})</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-900 text-blue-300">
                                        {{ $olt->total_pon_ports }} PON
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    {{ $olt->location ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $colors = [
                                            'active' => 'green',
                                            'offline' => 'red',
                                            'maintenance' => 'yellow'
                                        ];
                                        $color = $colors[$olt->status] ?? 'gray';
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $color }}-900 text-{{ $color }}-300 capitalize">
                                        {{ $olt->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex gap-2">
                                    <a href="{{ route('network.olts.edit', $olt) }}" class="text-blue-400 hover:text-blue-300">Edit</a>
                                    <form action="{{ route('network.olts.destroy', $olt) }}" method="POST" onsubmit="return confirm('Delete OLT? This might affect linked ODPs.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center">No OLT found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $olts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
