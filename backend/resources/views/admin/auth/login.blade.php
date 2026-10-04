<!DOCTYPE html>
<html lang="en" class="h-full bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — RiderON</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-text-primary antialiased">
    <div class="flex min-h-screen">
        <div class="relative hidden flex-1 flex-col justify-between overflow-hidden bg-secondary p-12 text-text-inverse lg:flex">
            <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #FF6A00 0, transparent 40%), radial-gradient(circle at 80% 70%, #2563EB 0, transparent 45%);"></div>

            <div class="relative flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-primary text-body-strong font-heading text-white shadow-card">R</span>
                <span class="text-h2 font-heading text-white">RiderON</span>
            </div>

            <div class="relative max-w-md">
                <p class="text-display font-heading leading-tight text-white">Same-day delivery, fully under control.</p>
                <p class="mt-4 text-body text-text-inverse/70">Manage orders, partners, pricing and support from one console built for speed.</p>
            </div>

            <p class="relative text-caption text-text-inverse/40">&copy; {{ date('Y') }} RiderON. All rights reserved.</p>
        </div>

        <div class="flex flex-1 items-center justify-center bg-background p-6">
            <div class="w-full max-w-sm">
                <div class="mb-8 text-center lg:hidden">
                    <span class="text-display font-heading text-secondary">RiderON</span>
                    <p class="mt-1 text-body text-text-secondary">Admin panel</p>
                </div>

                <div class="mb-6 hidden lg:block">
                    <h1 class="text-h1 font-heading text-text-primary">Sign in</h1>
                    <p class="mt-1 text-body text-text-secondary">Enter your admin credentials to continue.</p>
                </div>

                <div class="card">
                    @if ($errors->any())
                        <div class="mb-4 flex items-start gap-2 rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-body text-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <ul class="list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email or phone</label>
                            <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus class="form-input">
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" name="password" required class="form-input">
                        </div>

                        <label class="flex items-center gap-2 text-caption text-text-secondary">
                            <input type="checkbox" name="remember" value="1" class="rounded border-border text-primary focus:ring-primary/40">
                            Remember me
                        </label>

                        <button type="submit" class="btn-primary w-full">Log in</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
