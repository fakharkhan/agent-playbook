@extends('layouts.app')

@section('title', 'Table of Contents — AI for Artisans')

@section('content')
<div class="pt-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        {{-- Header --}}
        <div class="text-center mb-16">
            <h1 class="text-4xl sm:text-5xl font-bold text-white tracking-tight">Table of Contents</h1>
            <p class="mt-4 text-lg text-slate-400">
                AI for Artisans — Mastering the Laravel AI SDK
            </p>
            <div class="mt-6 flex items-center justify-center gap-3 text-sm text-slate-500">
                <span>By Fakhar Zaman Khan</span>
                <span class="text-slate-700">|</span>
                <span>First Edition, 2026</span>
                <span class="text-slate-700">|</span>
                <span>Laravel 12 &middot; PHP 8.4+</span>
            </div>
        </div>

        {{-- Chapters List --}}
        <div class="space-y-4">
            @foreach($chapters as $slug => $chapter)
                <a href="{{ route('book.show', $slug) }}" class="group block rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.05] hover:border-white/10 transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-5">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-red-500/20 to-orange-500/20 text-base font-bold text-red-400 border border-red-500/20">
                                @if($chapter['part'] === 'Front Matter')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    </svg>
                                @elseif($chapter['part'] === 'Appendices')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                @else
                                    {{ str_contains($chapter['part'], '&') ? 'VI' : substr($chapter['part'], -1) }}
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <h2 class="text-xl font-semibold text-white group-hover:text-red-400 transition-colors">
                                    {{ $chapter['title'] }}
                                </h2>

                                @if(isset($chapter['chapters']))
                                    <div class="mt-3 space-y-1.5">
                                        @foreach($chapter['chapters'] as $subChapter)
                                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                                <div class="h-1 w-1 rounded-full bg-slate-600 shrink-0"></div>
                                                <span>{{ $subChapter }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <svg class="h-6 w-6 shrink-0 text-slate-600 group-hover:text-red-400 transition-all group-hover:translate-x-1 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Start Reading CTA --}}
        <div class="mt-16 text-center">
            <a href="{{ route('book.show', 'preface') }}" class="inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 px-8 py-4 text-lg font-semibold text-white shadow-2xl shadow-red-500/25 hover:shadow-red-500/40 transition-all duration-300 group">
                Begin with the Preface
                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </div>
</div>
@endsection
