@extends('layouts.app')

@section('title', 'AI for Artisans — Mastering the Laravel AI SDK')

@section('content')
{{-- Hero Section --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
    {{-- Animated gradient background --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(239,68,68,0.15),transparent)]"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_50%_80%_at_80%_50%,rgba(249,115,22,0.08),transparent)]"></div>

    {{-- Grid pattern overlay --}}
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:64px_64px]"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
        <div class="flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
            {{-- Left: Text content --}}
            <div class="flex-1 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-1.5 text-sm text-red-400 mb-8">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    First Edition — Laravel 12 &amp; PHP 8.4
                </div>

                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight leading-[1.1]">
                    <span class="text-white">AI for</span><br>
                    <span class="bg-gradient-to-r from-red-400 via-orange-400 to-amber-400 bg-clip-text text-transparent">Artisans</span>
                </h1>

                <p class="mt-6 text-xl sm:text-2xl text-slate-300 font-medium leading-relaxed">
                    Mastering the Laravel AI SDK
                </p>

                <p class="mt-4 text-lg text-slate-400 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    The definitive guide to building AI-powered applications with Laravel.
                    From agents and embeddings to streaming, RAG, and MCP &mdash; everything you need to ship production AI features.
                </p>

                <p class="mt-4 text-base text-slate-500">
                    By <span class="text-slate-300 font-medium">Fakhar Zaman Khan</span>
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="{{ route('book.index') }}" class="inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 px-8 py-4 text-lg font-semibold text-white shadow-2xl shadow-red-500/25 hover:shadow-red-500/40 hover:from-red-400 hover:to-orange-400 transition-all duration-300 group">
                        Start Reading
                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="https://github.com/laravel/ai" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-4 text-lg font-medium text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-300">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        View on GitHub
                    </a>
                </div>
            </div>

            {{-- Right: Book visual --}}
            <div class="flex-shrink-0 relative">
                <div class="relative w-72 sm:w-80 lg:w-96">
                    {{-- Glow effect --}}
                    <div class="absolute -inset-8 bg-gradient-to-r from-red-500/20 to-orange-500/20 rounded-3xl blur-3xl"></div>

                    {{-- Book cover --}}
                    <div class="relative rounded-2xl border border-white/10 bg-gradient-to-br from-slate-800 to-slate-900 p-8 shadow-2xl">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 via-orange-500 to-amber-500 rounded-t-2xl"></div>

                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                    </svg>
                                </div>
                                <div class="h-px flex-1 bg-gradient-to-r from-white/20 to-transparent"></div>
                            </div>

                            <div>
                                <h3 class="text-2xl font-bold text-white">AI for Artisans</h3>
                                <p class="mt-1 text-sm text-slate-400">Mastering the Laravel AI SDK</p>
                            </div>

                            {{-- Code preview --}}
                            <div class="rounded-lg bg-slate-950/80 p-4 font-mono text-xs leading-relaxed border border-white/5">
                                <div class="text-slate-500">// Create an AI agent</div>
                                <div><span class="text-red-400">$agent</span> = <span class="text-orange-400">Agent</span>::<span class="text-amber-400">create</span>();</div>
                                <div class="mt-2 text-slate-500">// Ask anything</div>
                                <div><span class="text-red-400">$response</span> = <span class="text-red-400">$agent</span></div>
                                <div class="pl-4">-><span class="text-amber-400">prompt</span>(<span class="text-green-400">'Hello!'</span>);</div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>By Fakhar Zaman Khan</span>
                                <span>2026 &middot; Laravel 12</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section class="relative py-24 sm:py-32">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900/50 to-slate-950"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-white">What You'll Learn</h2>
            <p class="mt-4 text-lg text-slate-400 max-w-2xl mx-auto">
                22 chapters covering every aspect of AI development with Laravel, from basics to production-ready patterns.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
                $features = [
                    ['icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z', 'title' => 'AI Agents', 'desc' => 'Build intelligent agents with conversations, memory, tools, structured output, and middleware.', 'color' => 'from-red-500 to-rose-500'],
                    ['icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z', 'title' => 'Multimodal AI', 'desc' => 'Generate images, convert text to speech, transcribe audio, and handle file attachments.', 'color' => 'from-orange-500 to-amber-500'],
                    ['icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z', 'title' => 'Embeddings & RAG', 'desc' => 'Vector embeddings, semantic search, retrieval-augmented generation, and document reranking.', 'color' => 'from-amber-500 to-yellow-500'],
                    ['icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z', 'title' => 'Real-Time Streaming', 'desc' => 'SSE streaming, Vercel AI protocol, broadcasting, and background AI processing with queues.', 'color' => 'from-emerald-500 to-teal-500'],
                    ['icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'title' => 'Production Patterns', 'desc' => 'Failover, rate limiting, resilience, monitoring, and comprehensive AI testing strategies.', 'color' => 'from-blue-500 to-indigo-500'],
                    ['icon' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.556a4.5 4.5 0 00-1.242-7.244l4.5-4.5a4.5 4.5 0 016.364 6.364l-1.757 1.757', 'title' => 'MCP & Projects', 'desc' => 'Model Context Protocol servers, plus three complete real-world projects you can ship.', 'color' => 'from-violet-500 to-purple-500'],
                ];
            @endphp

            @foreach($features as $feature)
                <div class="group relative rounded-2xl border border-white/5 bg-white/[0.02] p-6 hover:bg-white/[0.05] hover:border-white/10 transition-all duration-300">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $feature['color'] }} shadow-lg mb-4">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Chapter Overview Section --}}
<section class="relative py-24 sm:py-32">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950 to-slate-900/50"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-white">Inside the Book</h2>
            <p class="mt-4 text-lg text-slate-400 max-w-2xl mx-auto">
                A complete journey from your first AI interaction to production-ready applications.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($chapters as $slug => $chapter)
                <a href="{{ route('book.show', $slug) }}" class="group flex items-start gap-4 rounded-xl border border-white/5 bg-white/[0.02] p-5 hover:bg-white/[0.05] hover:border-white/10 transition-all duration-300">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-red-500/20 to-orange-500/20 text-sm font-bold text-red-400 border border-red-500/20">
                        {{ $chapter['part'] === 'Front Matter' ? 'P' : (str_contains($chapter['part'], 'Appendices') ? 'A' : substr($chapter['part'], -1)) }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-white group-hover:text-red-400 transition-colors truncate">{{ $chapter['title'] }}</h3>
                        @if(isset($chapter['chapters']))
                            <p class="mt-1 text-sm text-slate-500 truncate">{{ implode(' · ', array_slice($chapter['chapters'], 0, 3)) }}{{ count($chapter['chapters']) > 3 ? ' …' : '' }}</p>
                        @endif
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-slate-600 group-hover:text-red-400 transition-colors mt-0.5 ml-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('book.index') }}" class="inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 px-8 py-4 text-lg font-semibold text-white shadow-2xl shadow-red-500/25 hover:shadow-red-500/40 transition-all duration-300 group">
                Start Reading Now
                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- Tech Stack Section --}}
<section class="relative py-24">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/50 to-slate-950"></div>
    <div class="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-3 flex-wrap justify-center text-sm text-slate-500">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5">Laravel 12</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5">PHP 8.4+</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5">laravel/ai v0.2.x</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5">22 Chapters</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5">3 Real-World Projects</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5">First Edition 2026</span>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="border-t border-white/5 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-slate-500">&copy; {{ date('Y') }} Fakhar Zaman Khan. AI for Artisans.</p>
            <div class="flex items-center gap-6 text-sm text-slate-500">
                <a href="{{ route('book.index') }}" class="hover:text-white transition-colors">Table of Contents</a>
                <a href="https://github.com/laravel/ai" target="_blank" class="hover:text-white transition-colors">GitHub</a>
                <a href="https://laravel.com" target="_blank" class="hover:text-white transition-colors">Laravel</a>
            </div>
        </div>
        <p class="mt-6 text-xs text-slate-600 text-center max-w-3xl mx-auto leading-relaxed">
            This is an independent, community-authored guide. It is not an official Laravel publication and is not affiliated with, endorsed by, or connected to Laravel LLC or Taylor Otwell. Laravel is created and maintained by Taylor Otwell. The Laravel AI SDK is built by Taylor Otwell and its contributors.
        </p>
    </div>
</footer>
@endsection
