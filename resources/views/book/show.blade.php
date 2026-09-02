@extends('layouts.app')

@section('title', $chapter['title'] . ' — The A.G.E.N.T. Playbook')

@section('content')
<div class="pt-16 flex">
    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed top-16 left-0 z-40 h-[calc(100vh-4rem)] w-72 -translate-x-full lg:translate-x-0 border-r border-white/5 bg-slate-950/95 backdrop-blur-xl transition-transform duration-300 overflow-y-auto">
        <div class="p-4">
            <a href="{{ route('book.index') }}" class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors mb-6 group">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Table of Contents
            </a>

            <nav class="space-y-1">
                @foreach($chapters as $s => $ch)
                    <a href="{{ route('book.show', $s) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors {{ $s === $slug ? 'bg-red-500/10 text-red-400 font-medium border border-red-500/20' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-xs font-bold {{ $s === $slug ? 'bg-red-500/20 text-red-400' : 'bg-white/5 text-slate-500' }}">
                            @if($ch['part'] === 'Front Matter')
                                P
                            @elseif($ch['part'] === 'Appendix' || $ch['part'] === 'Appendices')
                                A
                            @else
                                {{ str_contains($ch['part'], '&') ? '6' : substr($ch['part'], -1) }}
                            @endif
                        </span>
                        <span class="truncate">{{ $ch['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </aside>

    {{-- Mobile sidebar toggle --}}
    <button id="sidebar-toggle" class="fixed bottom-6 left-6 z-50 lg:hidden flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-r from-red-500 to-orange-500 text-white shadow-2xl shadow-red-500/25">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>
    <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm hidden lg:hidden"></div>

    {{-- Main content --}}
    <div class="w-full lg:pl-72">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            {{-- Chapter header --}}
            <div class="mb-12">
                <div class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-1.5 text-sm text-red-400 mb-4">
                    {{ $chapter['part'] }}
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">{{ $chapter['title'] }}</h1>
            </div>

            {{-- Book content --}}
            <article class="book-content">
                {!! $content !!}
            </article>

            {{-- Navigation --}}
            <div class="mt-16 flex items-center justify-between gap-4 border-t border-white/5 pt-8">
                @if($prevSlug)
                    <a href="{{ route('book.show', $prevSlug) }}" class="group flex items-center gap-3 text-sm text-slate-400 hover:text-white transition-colors">
                        <svg class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        <span class="hidden sm:inline">{{ $prevChapter['title'] }}</span>
                        <span class="sm:hidden">Previous</span>
                    </a>
                @else
                    <div></div>
                @endif

                @if($nextSlug)
                    <a href="{{ route('book.show', $nextSlug) }}" class="group flex items-center gap-3 text-sm text-slate-400 hover:text-white transition-colors">
                        <span class="hidden sm:inline">{{ $nextChapter['title'] }}</span>
                        <span class="sm:hidden">Next</span>
                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                @else
                    <div></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    toggle.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    });

    overlay.addEventListener('click', closeSidebar);
</script>
@endsection
