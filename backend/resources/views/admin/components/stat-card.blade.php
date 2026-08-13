@php
    $palette = match ($color ?? 'default') {
        'success' => ['text' => 'text-success', 'bg' => 'bg-success/10', 'icon' => 'text-success'],
        'warning' => ['text' => 'text-warning', 'bg' => 'bg-warning/10', 'icon' => 'text-warning'],
        'danger' => ['text' => 'text-danger', 'bg' => 'bg-danger/10', 'icon' => 'text-danger'],
        'info' => ['text' => 'text-info', 'bg' => 'bg-info/10', 'icon' => 'text-info'],
        default => ['text' => 'text-text-primary', 'bg' => 'bg-primary/10', 'icon' => 'text-primary'],
    };
    $iconPath = match ($icon ?? 'chart') {
        'money' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'truck' => 'M1 3h13v13H1V3zm13 5h4l3 3v5h-7V8zM5.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm12 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'alert' => 'M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'user' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m20 0v-2a4 4 0 00-3-3.87M13 3.13a4 4 0 010 7.75M9 11a4 4 0 100-8 4 4 0 000 8z',
        'shield' => 'M12 2l8 4v6c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-4z',
        default => 'M9 19V6l7 3.5V19M4 19h16M9 19l-5-2.5V9L9 6',
    };
@endphp
<div class="stat-card">
    <span class="stat-icon {{ $palette['bg'] }} {{ $palette['icon'] }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="{{ $iconPath }}" />
        </svg>
    </span>
    <div>
        <p class="text-caption text-text-secondary">{{ $label }}</p>
        <p class="mt-0.5 text-h1 font-heading {{ $palette['text'] }}">{{ $value }}</p>
    </div>
</div>
