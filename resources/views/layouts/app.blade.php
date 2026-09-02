<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="The A.G.E.N.T. Playbook — Fakhar Khan's applied guide to redesigning software delivery workflows for agentic AI. Audit, Gauge, Engineer, Navigate, Track.">

    <title>@yield('title', 'The A.G.E.N.T. Playbook — Agentic AI for Software Delivery')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased">
    <nav class="fixed top-0 left-0 right-0 z-50 border-b border-white/10 bg-slate-950/80 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-red-800 to-red-950 shadow-lg shadow-red-900/30 border border-red-700/40">
                        <span class="text-xs font-bold text-red-100 tracking-tight">AG</span>
                    </div>
                    <span class="text-lg font-semibold tracking-tight text-white group-hover:text-red-300 transition-colors">The A.G.E.N.T. Playbook</span>
                </a>

                <div class="flex items-center gap-4">
                    <a href="https://fakharkhan.com" class="hidden sm:flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors" rel="noopener noreferrer">
                        fakharkhan.com
                    </a>
                    <a href="{{ route('book.index') }}" class="rounded-lg bg-red-900/40 border border-red-700/30 px-4 py-2 text-sm font-medium text-red-100 hover:bg-red-800/50 transition-colors">
                        Read the Book
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-white/5 bg-slate-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
            <p class="text-center text-sm text-slate-500">
                &copy; {{ date('Y') }} Fakhar Khan · Soft Pyramid LLC ·
                <a href="https://fakharkhan.com" class="text-slate-400 hover:text-white transition-colors" rel="noopener noreferrer">fakharkhan.com</a>
            </p>
            <p class="text-center text-xs text-slate-600 mt-2">
                Developed during the Harvard Data Science Review Agentic AI Intensive (April 2026)
            </p>
        </div>
    </footer>
</body>
</html>
