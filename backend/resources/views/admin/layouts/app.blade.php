<!DOCTYPE html>
<html lang="en" class="h-full bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — RiderON Admin</title>

    {{-- Poppins for headings, Inter for body — mirrors packages/design-tokens/src/typography.ts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-body text-text-primary antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 flex-col bg-secondary text-text-inverse lg:flex">
            <div class="flex items-center gap-2.5 px-6 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-body-strong font-heading text-white shadow-card">R</span>
                <div class="leading-tight">
                    <span class="block text-body-strong font-heading text-white">RiderON</span>
                    <span class="block text-micro uppercase tracking-wide text-text-inverse/50">Admin console</span>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'grid'],
                        ['label' => 'Cities & Stations', 'route' => 'admin.cities.index', 'match' => 'admin.cities.*|admin.stations.*', 'icon' => 'map'],
                        ['label' => 'Routes & Schedules', 'route' => 'admin.routes.index', 'match' => 'admin.routes.*', 'icon' => 'route'],
                        ['label' => 'Pricing Rules', 'route' => 'admin.pricing-rules.index', 'match' => 'admin.pricing-rules.*', 'icon' => 'tag'],
                        ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'icon' => 'users'],
                        ['label' => 'Delivery Partners', 'route' => 'admin.delivery-partners.index', 'match' => 'admin.delivery-partners.*', 'icon' => 'truck'],
                        ['label' => 'Orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.index|admin.orders.show', 'icon' => 'box'],
                        ['label' => 'Recheck Orders', 'route' => 'admin.orders.recheck', 'match' => 'admin.orders.recheck', 'icon' => 'check'],
                        ['label' => 'Refunds', 'route' => 'admin.refunds.index', 'match' => 'admin.refunds.*', 'icon' => 'refund'],
                        ['label' => 'Support Tickets', 'route' => 'admin.support-tickets.index', 'match' => 'admin.support-tickets.*', 'icon' => 'chat'],
                        ['label' => 'OTP Logs', 'route' => 'admin.otp-logs.index', 'match' => 'admin.otp-logs.*', 'icon' => 'shield'],
                        ['label' => 'Activity', 'route' => 'admin.notifications.index', 'match' => 'admin.notifications.*', 'icon' => 'bell'],
                    ];

                    $icons = [
                        'grid' => 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z',
                        'map' => 'M9 20l-6-3V4l6 3 6-3 6 3v13l-6-3-6 3zm0-13v13m6-16v13',
                        'route' => 'M4 6a2 2 0 100 4 2 2 0 000-4zm0 0v3a5 5 0 005 5h6a5 5 0 015 5v0m0 0a2 2 0 100 4 2 2 0 000-4z',
                        'tag' => 'M20.59 13.41L11 3.83A2 2 0 009.59 3H4a1 1 0 00-1 1v5.59a2 2 0 00.59 1.41l9.59 9.59a2 2 0 002.82 0l4.59-4.59a2 2 0 000-2.83zM7 7h.01',
                        'users' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m20 0v-2a4 4 0 00-3-3.87M13 3.13a4 4 0 010 7.75M9 11a4 4 0 100-8 4 4 0 000 8z',
                        'truck' => 'M1 3h13v13H1V3zm13 5h4l3 3v5h-7V8zM5.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm12 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
                        'box' => 'M21 8L12 3 3 8l9 5 9-5zM3 8v10l9 5 9-5V8M12 13v10',
                        'refund' => 'M3 10h11a5 5 0 010 10H8m-5-10l4-4m-4 4l4 4',
                        'chat' => 'M21 11.5a8.38 8.38 0 01-8.5 8.4 8.5 8.5 0 01-4-1L3 20l1.1-5.5A8.4 8.4 0 013 11.5 8.38 8.38 0 0111.5 3a8.5 8.5 0 019.5 8.5z',
                        'shield' => 'M12 2l8 4v6c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-4z',
                        'bell' => 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9zM13.73 21a2 2 0 01-3.46 0',
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @if (Route::has($item['route']))
                        <a
                            href="{{ route($item['route']) }}"
                            class="nav-link {{ request()->routeIs(explode('|', $item['match'])) ? 'nav-link-active' : '' }}"
                        >
                            <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="{{ $icons[$item['icon']] }}" />
                                </svg>
                            </span>
                            <span class="text-body-strong">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-white/10 px-4 py-4 text-micro text-text-inverse/40">
                &copy; {{ date('Y') }} RiderON
            </div>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-border bg-surface px-6 py-4 shadow-sm">
                <div>
                    <h1 class="text-h1 font-heading text-text-primary">@yield('title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-4">
                    @auth('web')
                        <div class="flex items-center gap-2.5 rounded-pill border border-border bg-background py-1.5 pl-1.5 pr-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-micro font-medium text-white">
                                {{ strtoupper(substr(auth('web')->user()->name ?? 'A', 0, 1)) }}
                            </span>
                            <span class="text-caption text-text-secondary">
                                {{ auth('web')->user()->name }} &middot; {{ ucfirst(auth('web')->user()->role) }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">Log out</button>
                        </form>
                    @endauth
                </div>
            </header>

            <main class="flex-1 p-6">
                @if (session('status'))
                    <div class="mb-4 flex items-center gap-2 rounded-md border border-success/30 bg-success/10 px-4 py-3 text-body text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 flex items-center gap-2 rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-body text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-body text-danger">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
