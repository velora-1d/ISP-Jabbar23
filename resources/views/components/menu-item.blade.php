@props(['route', 'label', 'icon', 'active' => false, 'soon' => false, 'colorFrom' => 'blue-500', 'colorTo' => 'cyan-500'])

@php
$isActive = $active ?: request()->routeIs($route);
$baseColor = explode('-', $colorFrom)[0];
@endphp

<li>
    <a href="{{ $soon ? '#' : route($route) }}" 
       class="flex items-center p-3 rounded-xl transition-all duration-200 group
          {{ $isActive 
             ? "bg-gradient-to-r from-{$baseColor}-600/20 to-{$colorTo}/20 text-white border border-{$baseColor}-500/30 shadow-lg shadow-{$baseColor}-500/10" 
             : ($soon ? 'text-gray-500 cursor-not-allowed opacity-50' : 'text-gray-400 hover:bg-gray-700/50 hover:text-white') }}">
        <div class="p-2 rounded-lg bg-gradient-to-br from-{{ $colorFrom }} to-{{ $colorTo }} {{ $isActive ? '' : ($soon ? 'opacity-40' : 'opacity-60 group-hover:opacity-100') }}">
            {!! $icon !!}
        </div>
        <span class="flex-1 ms-3 font-semibold">{{ $label }}</span>
        @if($soon)
            <span class="px-1.5 py-0.5 text-[10px] bg-gray-700 text-gray-500 rounded">S</span>
        @endif
    </a>
</li>
