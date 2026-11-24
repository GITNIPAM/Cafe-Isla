@php
    $size = $size ?? 48;
    $dark = $dark ?? false;
    $ink = $dark ? '#F6F1E6' : '#1C3127';
    $accent = '#C4553A';
    $uid = 'isla_seal_' . \Illuminate\Support\Str::random(6);
@endphp
<svg
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 120 120"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    class="{{ $class ?? '' }}"
    aria-hidden="true"
>
    <defs>
        <path id="{{ $uid }}_top" d="M 18.5,60 A 41.5,41.5 0 1,1 101.5,60"/>
        <path id="{{ $uid }}_bot" d="M 13.5,60 A 46.5,46.5 0 1,0 106.5,60"/>
    </defs>

    <!-- Outer Architectural Rings -->
    <circle cx="60" cy="60" r="56.5" stroke="{{ $ink }}" stroke-width="2"/>
    <circle cx="60" cy="60" r="52" stroke="{{ $ink }}" stroke-width="0.7" stroke-opacity="0.5"/>
    <circle cx="60" cy="60" r="34.5" stroke="{{ $ink }}" stroke-width="1.35"/>

    <!-- Side Separator Dots -->
    <circle cx="16.5" cy="60" r="1.6" fill="{{ $accent }}"/>
    <circle cx="103.5" cy="60" r="1.6" fill="{{ $accent }}"/>

    <!-- Curved Roastery Stamp Typography -->
    <text fill="{{ $ink }}" font-family="Archivo, sans-serif" font-size="8.4" font-weight="700" letter-spacing="2.4">
        <textPath href="#{{ $uid }}_top" startOffset="50%" text-anchor="middle">CAFÉ ISLA</textPath>
    </text>
    <text fill="{{ $ink }}" fill-opacity="0.82" font-family="Archivo, sans-serif" font-size="6.4" font-weight="600" letter-spacing="1.7">
        <textPath href="#{{ $uid }}_bot" startOffset="50%" text-anchor="middle">ARCHIPELAGO · 2026</textPath>
    </text>

    <!-- Centerpiece: Terracotta Sun + Sun Rays + Coastal Palm / Tea Leaf + Horizon Waves -->
    <g stroke-linecap="round" stroke-linejoin="round">
        <!-- Sun Rays -->
        <line x1="60" y1="33" x2="60" y2="37" stroke="{{ $accent }}" stroke-width="1.3"/>
        <line x1="44" y1="39.5" x2="47" y2="42.5" stroke="{{ $accent }}" stroke-width="1.3"/>
        <line x1="76" y1="39.5" x2="73" y2="42.5" stroke="{{ $accent }}" stroke-width="1.3"/>
        <line x1="36.5" y1="53" x2="40.5" y2="54" stroke="{{ $accent }}" stroke-width="1.3"/>
        <line x1="83.5" y1="53" x2="79.5" y2="54" stroke="{{ $accent }}" stroke-width="1.3"/>

        <!-- Terracotta Sun -->
        <path d="M 43,64 A 17,17 0 0,1 77,64 Z" fill="{{ $accent }}"/>

        <!-- Botanical Leaf / Palm Frond Silhouette Rising from Sun -->
        <path d="M 60,64 C 54,57 54,48 60,42 C 66,48 66,57 60,64 Z" fill="{{ $dark ? '#1C3127' : '#F6F1E6' }}" stroke="{{ $ink }}" stroke-width="1.35"/>
        <line x1="60" y1="45" x2="60" y2="63.5" stroke="{{ $ink }}" stroke-width="1.1"/>

        <!-- Dual Coastal Horizon Waves -->
        <path d="M 32,67.5 C 40,63.5 48,71 56,67.5 C 64,64 72,71 80,67.5 C 83.5,66 86,66.5 88,67.5" stroke="{{ $ink }}" stroke-width="1.8"/>
        <path d="M 39,75 C 46,72 52,78 60,75 C 68,72 74,78 81,75" stroke="{{ $accent }}" stroke-width="1.5"/>
    </g>
</svg>
