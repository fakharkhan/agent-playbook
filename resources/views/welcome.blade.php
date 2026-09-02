@extends('layouts.app')

@section('title', 'The A.G.E.N.T. Playbook — Agentic AI for Software Delivery')

@section('content')
<section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(165,28,48,0.18),transparent)]"></div>
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:64px_64px]"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
        <a href="https://fakharkhan.com" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-300 transition-colors mb-8 group" rel="noopener noreferrer">
            <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to fakharkhan.com
        </a>

        <div class="flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
            <div class="flex-1 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 rounded-full border border-red-800/30 bg-red-950/40 px-4 py-1.5 text-sm text-red-200 mb-8">
                    Harvard Data Science Review · Agentic AI Intensive · April 2026
                </div>

                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight leading-[1.1]">
                    <span class="text-white">The</span><br>
                    <span class="bg-gradient-to-r from-red-300 via-red-400 to-red-200 bg-clip-text text-transparent">A.G.E.N.T.</span><br>
                    <span class="text-white">Playbook</span>
                </h1>

                <p class="mt-6 text-xl sm:text-2xl text-slate-300 font-medium leading-relaxed">
                    Redesigning software delivery for agentic AI
                </p>

                <p class="mt-4 text-lg text-slate-400 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    A practitioner's field guide: Audit, Gauge, Engineer, Navigate, and Track — applied to real client delivery workflows at Soft Pyramid LLC.
                </p>

                <p class="mt-4 text-base text-slate-500">
                    By <a href="https://fakharkhan.com" class="text-slate-300 font-medium hover:text-white transition-colors underline decoration-slate-500 hover:decoration-slate-400 underline-offset-2" rel="noopener noreferrer">Fakhar Khan</a>
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="{{ route('book.index') }}" class="inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-red-800 to-red-950 px-8 py-4 text-lg font-semibold text-white shadow-2xl shadow-red-900/30 hover:from-red-700 hover:to-red-900 transition-all duration-300 group border border-red-700/40">
                        Start Reading
                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="{{ route('book.show', 'preface') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-4 text-lg font-medium text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-300">
                        Read the Preface
                    </a>
                </div>
            </div>

            <div class="flex-shrink-0 relative">
                <div class="relative w-72 sm:w-80 lg:w-96">
                    <div class="absolute -inset-8 bg-gradient-to-r from-red-900/20 to-red-950/20 rounded-3xl blur-3xl"></div>
                    <div class="relative rounded-2xl border border-white/10 bg-gradient-to-br from-slate-800 to-slate-900 p-8 shadow-2xl">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-800 via-red-600 to-red-400 rounded-t-2xl"></div>
                        <div class="space-y-6">
                            <div class="text-center">
                                <p class="text-xs uppercase tracking-widest text-red-300/80">Framework</p>
                                <p class="mt-2 text-3xl font-bold text-white tracking-wide">A · G · E · N · T</p>
                            </div>
                            <dl class="space-y-3 text-sm">
                                @foreach (['Audit — map the workflow', 'Gauge — score outcomes', 'Engineer — redesign agent-first', 'Navigate — human-agent trust', 'Track — measure what matters'] as $item)
                                    <div class="flex gap-3 text-slate-300">
                                        <span class="text-red-400 shrink-0">→</span>
                                        <span>{{ $item }}</span>
                                    </div>
                                @endforeach
                            </dl>
                            <p class="text-xs text-slate-500 text-center pt-2 border-t border-white/5">
                                Case study: Implementation &amp; integration · CI triage + MR review pilot
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="relative border-t border-white/5 bg-slate-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,420px)] lg:items-center lg:gap-16">
            <div class="max-w-xl">
                <p class="text-xs uppercase tracking-widest text-red-300/80">Program credential</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-white tracking-tight">
                    Harvard Data Science Review
                </h2>
                <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                    This playbook grew out of <strong class="font-semibold text-white">Agentic AI: Contextualized and Applied</strong>, a 2.5-week intensive presented by Harvard Data Science Review (April 14–30, 2026).
                </p>
                <p class="mt-4 text-slate-400 leading-relaxed">
                    The A.G.E.N.T. framework and pilot charter in these pages are applied practitioner work — not official Harvard curriculum — but the program shaped how the workflows were audited, scored, and measured.
                </p>
                <p class="mt-6 text-sm text-slate-500">
                    Verification code: <span class="font-mono text-slate-400">KKNW-EQWZ</span>
                </p>
            </div>

            <figure class="relative mx-auto w-full max-w-sm lg:max-w-none">
                <div class="absolute -inset-4 rounded-2xl bg-gradient-to-br from-red-900/20 to-transparent blur-2xl"></div>
                <div class="relative overflow-hidden rounded-xl border border-white/10 bg-white shadow-2xl shadow-black/40">
                    <img
                        src="{{ asset('images/harvard-agentic-ai-certificate.png') }}"
                        alt="Harvard Data Science Initiative certificate of completion for Fakhar Khan — Agentic AI: Contextualized and Applied, April 14–30, 2026"
                        width="800"
                        height="1035"
                        class="w-full h-auto"
                        loading="lazy"
                    />
                </div>
                <figcaption class="mt-4 text-center text-xs text-slate-500">
                    Certificate of completion · Presented by Harvard Data Science Review
                </figcaption>
            </figure>
        </div>
    </div>
</section>
@endsection
