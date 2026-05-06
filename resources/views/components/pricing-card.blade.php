@props([
    'title',
    'subtitle',
    'price',
    'period',
    'highlight' => false,
    'badge' => null,
    'buttonText' => 'Começar',
    'buttonClass' => '',
])

<div class="{{ $highlight
        ? 'text-white rounded-3xl p-10 shadow-2xl ring-4 scale-105 flex flex-col relative'
        : 'bg-white rounded-2xl border border-gray-200 p-10 shadow-sm hover:shadow-md transition flex flex-col'
    }}" style="{{ $highlight ? 'background-color: #286cda; ring-color: rgba(40, 108, 218, 0.3);' : '' }}">

    @if($badge)
        <span class="absolute top-6 left-6 text-xs bg-yellow-400 text-black px-3 py-1 rounded-full font-semibold">
            {{ $badge }}
        </span>
    @endif

    <h3 class="text-2xl font-semibold mt-6">{{ $title }}</h3>

    <p class="text-sm mt-1 mb-6 {{ $highlight ? 'text-white/80' : 'text-gray-500' }}">
        {{ $subtitle }}
    </p>

    <div class="mb-8">
        <div class="text-5xl font-semibold">{{ $price }}</div>
        <div class="text-sm mt-2 {{ $highlight ? 'text-white/80' : 'text-gray-500' }}">
            {{ $period }}
        </div>
    </div>

    <ul class="space-y-3 text-sm flex-1">
        {{ $slot }}
    </ul>

    <a class="mt-8 text-center py-3 rounded-xl font-semibold transition
        {{ $highlight
            ? 'text-white hover:opacity-90'
            : 'border text-white hover:opacity-90'
        }}
        {{ $buttonClass }}"
        style="{{ $highlight ? 'background-color: white; color: #286cda;' : 'border-color: #286cda; background-color: #286cda;' }}">
        {{ $buttonText }}
    </a>
</div>