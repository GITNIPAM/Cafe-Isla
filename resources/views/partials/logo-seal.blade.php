@php
    $size = $size ?? 54;
    $dark = $dark ?? false;
    $ringInk = $dark ? '#F6F1E6' : '#1C3127';
    $bezelBg = $dark ? '#1C3127' : '#F6F1E6';
    $medallionBg = $dark ? '#12221B' : '#1C3127';
    $cream = '#F6F1E6';
    $coral = '#D96245';
    $seafoam = '#7FB8A4';
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
        <path id="{{ $uid }}_bot" d="M 14,60 A 46,46 0 1,0 106,60"/>
        <clipPath id="{{ $uid }}_clip">
            <circle cx="60" cy="60" r="34.5"/>
        </clipPath>
    </defs>

    <!-- Outer Stamp Bezel -->
    <circle cx="60" cy="60" r="56.5" fill="{{ $bezelBg }}" stroke="{{ $ringInk }}" stroke-width="2.5"/>
    <circle cx="60" cy="60" r="35.5" fill="{{ $medallionBg }}" stroke="{{ $ringInk }}" stroke-width="2"/>

    <!-- Coastal Four-Point Separator Stars -->
    <polygon points="16.5,57 17.8,59.2 20,60 17.8,60.8 16.5,63 15.2,60.8 13,60 15.2,59.2" fill="{{ $coral }}"/>
    <polygon points="103.5,57 104.8,59.2 107,60 104.8,60.8 103.5,63 102.2,60.8 100,60 102.2,59.2" fill="{{ $coral }}"/>

    <!-- Bold Ring Typography -->
    <text fill="{{ $ringInk }}" font-family="Archivo, sans-serif" font-size="9.2" font-weight="700" letter-spacing="2.4">
        <textPath href="#{{ $uid }}_top" startOffset="50%" text-anchor="middle">CAFÉ ISLA</textPath>
    </text>
    <text fill="{{ $ringInk }}" fill-opacity="0.9" font-family="Archivo, sans-serif" font-size="7.2" font-weight="700" letter-spacing="2.2">
        <textPath href="#{{ $uid }}_bot" startOffset="50%" text-anchor="middle">EST · 2026</textPath>
    </text>

    <!-- Coastal Café Centerpiece: Sun + Swaying Palm + Steaming Cup + Waves -->
    <g clip-path="url(#{{ $uid }}_clip)" stroke-linecap="round" stroke-linejoin="round">
        <!-- Warm Terracotta Coastal Sun -->
        <circle cx="53" cy="52" r="14.5" fill="{{ $coral }}"/>

        <!-- Swaying Coastal Palm Tree (Left-Center) -->
        <path d="M 41,73 C 42,61 46,51 53,42" stroke="{{ $cream }}" stroke-width="2.6"/>
        <!-- Sweeping Palm Fronds -->
        <path d="M 53,42 C 46,35 37,35 31,40" stroke="{{ $cream }}" stroke-width="2.1"/>
        <path d="M 53,42 C 49,33 43,29 37,30" stroke="{{ $cream }}" stroke-width="2.1"/>
        <path d="M 53,42 C 56,33 63,31 70,35" stroke="{{ $cream }}" stroke-width="2.1"/>
        <path d="M 53,42 C 61,39 68,42 72,48" stroke="{{ $cream }}" stroke-width="2.1"/>
        <path d="M 53,42 C 47,43 41,47 38,52" stroke="{{ $cream }}" stroke-width="2"/>

        <!-- Steaming Café Cup (Right-Center Shoreline) -->
        <path d="M 60,58 L 76,58 L 74.5,69 C 74,71.5 72,73 69.5,73 L 66.5,73 C 64,73 62,71.5 61.5,69 Z" fill="{{ $cream }}"/>
        <path d="M 76,60.5 C 79.5,60.5 79.5,67 75.5,67" stroke="{{ $cream }}" stroke-width="2"/>
        <!-- Gentle Steam Wisps -->
        <path d="M 65,54 C 64,51.5 66.5,49.5 65.5,47" stroke="{{ $cream }}" stroke-width="1.6"/>
        <path d="M 70.5,54 C 69.5,51 72,48.5 71,45.5" stroke="{{ $cream }}" stroke-width="1.6"/>

        <!-- Calm Coastal Ocean Waves -->
        <path d="M 24,76 C 36,72 48,79 60,76 C 72,73 84,79 96,75" stroke="{{ $seafoam }}" stroke-width="2.3"/>
        <path d="M 28,83 C 40,80 50,86 60,83 C 70,80 80,86 92,83" stroke="{{ $coral }}" stroke-width="2.1"/>
    </g>
</svg>
