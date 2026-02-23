# Part I — The Foundation

---

# Chapter 1: The AI Revolution Meets PHP

## 1.1 Why AI Matters for Laravel Developers

For nearly two decades, the typical web developer's toolkit has remained remarkably stable: a language, a framework, a database, a cache layer, a queue, and a search engine. You mastered these building blocks, and they carried you through project after project. PHP developers in particular built an extraordinary ecosystem around Laravel — one that handled authentication, payments, real-time broadcasting, full-text search, and server management with elegance and speed.

Then the world shifted.

In 2023, ChatGPT crossed 100 million users faster than any application in history. By 2024, AI-powered features had moved from novelty to expectation. Users began demanding intelligent search, conversational interfaces, automated content generation, and context-aware recommendations. By early 2025, the question was no longer *"Should we add AI to our product?"* but *"Why haven't we already?"*

For PHP developers, this shift created an uncomfortable gap. The AI ecosystem was dominated by Python. LangChain, LlamaIndex, Hugging Face — these were the tools that tutorials, blog posts, and conference talks centered around. If you wanted to build an AI-powered feature, the conventional wisdom was clear: spin up a Python microservice, call it from your Laravel app, and hope the two played nicely together.

That era ended in February 2026.

At **Laracon India**, **Taylor Otwell** — the creator and lead maintainer of Laravel — announced the official **Laravel AI SDK** — a first-party, batteries-included package for building AI-native applications directly in PHP. No Python sidecar. No awkward microservice boundaries. No context-switching between languages. Just the expressive, elegant API that millions of Laravel developers already knew, extended to encompass agents, image generation, audio synthesis, vector embeddings, RAG pipelines, and more.

The significance of this moment cannot be overstated. Laravel didn't just release a wrapper around an API. It released an *opinionated, full-featured AI framework* that treats artificial intelligence as a first-class citizen of the web application stack — just as Cashier treats payments, Scout treats search, and Sanctum treats authentication.

If you've been building with Laravel, you already have 90% of the skills you need. Agents are classes. Tools are classes. Structured output uses JSON schemas. Conversation memory uses database tables. Testing uses `::fake()` methods. Everything slots into patterns you've practiced for years.

This book will teach you the remaining 10%.

## 1.2 The State of AI in 2026

Before we dive into code, let's establish a shared vocabulary. The AI landscape in 2026 is broad, and understanding the key concepts will make every chapter that follows more intuitive.

### Large Language Models (LLMs)

LLMs are the engines behind modern AI. These are neural networks trained on vast corpora of text that can generate, summarize, translate, analyze, and reason about language. The leading models in 2026 include:

- **OpenAI's GPT series** — The model family that ignited the AI revolution. GPT-4o and its successors offer strong general-purpose reasoning, code generation, and multimodal capabilities.
- **Anthropic's Claude** — Known for nuanced reasoning, long context windows, and safety-oriented design. Claude Sonnet and Haiku offer a range of cost-performance tradeoffs.
- **Google's Gemini** — Google's multimodal model family, deeply integrated with Google's infrastructure and capable of processing text, images, audio, and video natively.
- **Open-source models** — DeepSeek, Mistral, and models served through Ollama provide self-hosted alternatives with competitive quality, giving developers full control over their data and costs.

For Laravel developers, the practical implication is this: you don't need to pick one. The Laravel AI SDK abstracts provider differences behind a unified API, and you can switch between them — or fail over automatically — with a single configuration change.

### Multimodal AI

Modern AI is no longer text-only. Multimodal models can process and generate across multiple types of content:

- **Text generation** — Chatbots, summarizers, content writers, code assistants.
- **Image generation** — Product photos, marketing materials, featured images, design prototypes. Providers like OpenAI (DALL-E), Gemini, and xAI offer generation APIs.
- **Text-to-Speech (TTS)** — Convert written content into natural-sounding audio. OpenAI and ElevenLabs lead in voice quality and customization.
- **Speech-to-Text (STT)** — Transcribe audio files into text, with optional speaker diarization (identifying who said what). OpenAI Whisper, ElevenLabs, and Mistral offer transcription APIs.

The Laravel AI SDK provides dedicated, fluent APIs for each modality. You'll use `Image::of()` to generate images, `Audio::of()` for speech synthesis, and `Transcription::fromStorage()` for transcription — all with the same queue, store, and test patterns.

### Embeddings and Vector Databases

Embeddings are the bridge between human language and machine understanding. When you generate an embedding for a piece of text, you convert it into a high-dimensional numerical vector — an array of floating-point numbers that captures the *semantic meaning* of that text. Similar concepts produce vectors that are close together in this mathematical space.

This enables **semantic search**: instead of matching keywords, you find content by meaning. A search for "best wineries in California" can match a document about "Napa Valley vineyards" even though they share no exact words.

Vector databases — or vector-capable extensions like **pgvector** for PostgreSQL — store these embeddings and enable fast similarity queries. Laravel's AI SDK integrates directly with pgvector through Eloquent, adding methods like `whereVectorSimilarTo()` to your query builder.

### Retrieval-Augmented Generation (RAG)

RAG is the architectural pattern that makes AI useful for your specific data. The problem with LLMs is that they only know what they were trained on. They don't know about your company's products, your internal documentation, or your customer's order history.

RAG solves this by combining retrieval (searching your data) with generation (asking the LLM to reason about what was found). The flow looks like this:

1. **User asks a question** — "What's our return policy for electronics?"
2. **Your app searches a knowledge base** — Using embeddings and vector similarity, it finds the three most relevant documents.
3. **The relevant documents are attached to the prompt** — The LLM receives both the question and the context.
4. **The LLM generates an answer** — Grounded in your actual data, not hallucinated from training.

The Laravel AI SDK supports RAG through multiple mechanisms: the `SimilaritySearch` tool for agent-driven retrieval, `FileSearch` for provider-managed vector stores, and direct `whereVectorSimilarTo()` Eloquent queries for custom implementations.

### AI Agents

Agents are the most important concept in this book. An agent is more than an API call — it's an autonomous entity with:

- **Instructions** — A system prompt that defines its personality, expertise, and constraints.
- **Memory** — Conversation history that lets it maintain context across interactions.
- **Tools** — Functions it can call to interact with external systems (databases, APIs, file systems).
- **Structured output** — A defined schema that ensures responses conform to a predictable format.

Think of an agent as a specialized employee you've hired for a specific job. A `SalesCoach` agent analyzes transcripts and scores sales performance. A `ContentWriter` agent generates SEO-optimized product descriptions. A `SupportBot` agent answers customer questions using your knowledge base. Each has its own instructions, tools, and output format.

In the Laravel AI SDK, agents are PHP classes. You'll spend much of this book learning to build them.

## 1.3 Laravel's AI Vision — SDK, MCP, and Boost

Laravel's approach to AI rests on three pillars, each serving a distinct purpose:

### Pillar 1: The AI SDK — Build AI-Powered Applications

The **Laravel AI SDK** (`laravel/ai`) is the core package and the subject of this book. It provides a unified, expressive API for:

- Building intelligent agents with tools, memory, and structured output
- Generating images, audio, and transcriptions
- Creating and querying vector embeddings
- Managing vector stores for RAG
- Reranking documents for search relevance
- Streaming, broadcasting, and queuing AI operations
- Testing every AI feature with fake-and-assert patterns

The SDK sits on top of **Prism PHP** (`prism-php/prism`), an open-source provider abstraction layer that handles the raw HTTP communication with AI providers. The Laravel AI SDK adds the framework integration: Artisan commands, service providers, queue support, broadcasting, Eloquent extensions, event dispatching, and testing utilities.

At the time of writing — just fifteen days after Taylor Otwell's initial release — the SDK has already reached **v0.2.1**, with **544 GitHub stars**, **41 contributors**, and **265 commits**. The pace of development is extraordinary, reflecting both the quality of the architecture Taylor and his team designed and the enthusiasm of the Laravel community.

### Pillar 2: Laravel MCP — Expose Your App to AI Clients

The **Model Context Protocol (MCP)** is an open standard, originally developed by Anthropic, that allows AI assistants to interact with external data sources and tools. **Laravel MCP** lets you turn your Laravel application into an MCP server — exposing your routes, data, and functionality to AI clients like Claude Desktop, Cursor, or any MCP-compatible tool.

While the AI SDK lets you *build applications that use AI*, MCP lets *AI use your application*. The two are complementary: you might build an AI-powered customer support bot with the SDK, and simultaneously expose your product catalog via MCP so that external AI assistants can query your inventory.

We cover Laravel MCP in detail in Part VI of this book.

### Pillar 3: Boost — AI-Powered Development

**Boost** is Laravel's AI-powered development assistant, designed to accelerate your workflow within the Laravel ecosystem. Think of it as an intelligent coding companion that understands Laravel's conventions, patterns, and best practices.

While Boost is a fascinating tool, it's a developer productivity tool rather than a feature you ship to users. This book focuses on the AI SDK — the tools you'll use to build AI features *into* your applications.

### The Three Pillars Together

| Pillar | Purpose | Direction |
|--------|---------|-----------|
| **AI SDK** | Build AI features into your app | Your app → AI providers |
| **Laravel MCP** | Expose your app to AI clients | AI clients → Your app |
| **Boost** | AI-assisted development | AI → Your code |

Together, these three tools represent Laravel's comprehensive vision for AI: you build AI features with the SDK, expose your application through MCP, and accelerate your development with Boost.

## 1.4 What You'll Build in This Book

Theory is essential, but this is a book for builders. Throughout the chapters, you'll work with practical examples that build toward three complete, production-quality projects in Part VII:

### Project 1: AI-Powered Customer Support Bot

A conversational support agent that uses RAG to answer questions from your knowledge base. You'll build:

- A `SupportAgent` with custom instructions and conversation memory
- A knowledge base powered by vector embeddings and pgvector
- Streaming responses delivered to a real-time chat interface
- Full conversation history with the `RemembersConversations` trait
- A comprehensive test suite using `Agent::fake()`

This project ties together agents, tools, embeddings, streaming, and testing — the core competencies of Part II through Part V.

### Project 2: E-Commerce Product Description Generator

An automated content pipeline that generates SEO-optimized product descriptions at scale. You'll build:

- A `DescriptionWriter` agent with structured output (title, description, meta tags, keywords)
- Bulk generation using Laravel's queue system
- Embedding-based deduplication to avoid generating similar content
- Provider failover for reliability

This project demonstrates structured output, queuing, embeddings, and failover patterns.

### Project 3: Multi-Modal Content Platform

A content management system where AI handles the heavy lifting across text, images, and audio. You'll build:

- A blog post generator that creates articles from topic prompts
- Automatic featured image generation using `Image::of()`
- Audio narration of articles using `Audio::of()`
- Semantic search across all content using vector embeddings

This project showcases the SDK's multimodal capabilities and demonstrates how text, image, audio, and search features compose together.

Each project builds on the concepts taught in the preceding chapters. By the time you reach Part VII, you'll have all the knowledge you need to build them — and the confidence to design your own AI-powered features.

---

# Chapter 2: Setting Up Your AI Development Environment

## 2.1 Prerequisites

Before installing the Laravel AI SDK, ensure your development environment meets the following requirements.

### PHP 8.4+

Laravel 12 — and by extension the AI SDK — requires **PHP 8.4 or higher**. PHP 8.4 introduced property hooks, asymmetric visibility, and other features that the framework relies on. Verify your version:

```bash
$ php -v
```

If you need to upgrade, tools like Homebrew (macOS), Laravel Herd, or Docker make this straightforward.

### Laravel 12

The AI SDK depends on `illuminate/*` components at version `^12.0`. You'll need a fresh Laravel 12 application or an existing project running Laravel 12. To create a new project:

```bash
$ composer create-project laravel/laravel my-ai-app
$ cd my-ai-app
```

### PostgreSQL with pgvector (Recommended)

While not strictly required for all SDK features, **PostgreSQL with the pgvector extension** is strongly recommended if you plan to use embeddings and semantic search. The SDK's `whereVectorSimilarTo()` Eloquent method requires pgvector. If you're using SQLite or MySQL for basic agent features, that's perfectly fine — you can add PostgreSQL later when you reach Part IV.

### Prism PHP

The SDK uses **Prism PHP** (`prism-php/prism`) as its provider abstraction layer. You don't need to install this separately — it's pulled in automatically as a dependency of `laravel/ai`. However, it's worth knowing it exists: if you ever need to debug provider-level behavior, Prism is where the HTTP calls happen.

## 2.2 Installing the Laravel AI SDK

Installation follows the standard Laravel package pattern: install, publish, migrate.

### Step 1: Install via Composer

```bash
$ composer require laravel/ai
```

This installs the SDK and all its dependencies, including Prism PHP and its provider drivers.

### Step 2: Publish Configuration and Migrations

```bash
$ php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

This publishes two things:

- **`config/ai.php`** — The configuration file where you define default providers, models, and caching settings.
- **Database migrations** — Migration files for conversation storage.

### Step 3: Run Migrations

```bash
$ php artisan migrate
```

This creates two tables in your database:

| Table | Purpose |
|-------|---------|
| `agent_conversations` | Stores conversation metadata (user, agent, timestamps) |
| `agent_conversation_messages` | Stores individual messages within conversations (role, content) |

These tables power the `RemembersConversations` trait, which gives agents automatic, persistent conversation memory. If you're only building stateless agents (no conversation history), the tables will simply remain empty — but they need to exist for the migrations to pass.

That's it. Three commands, and you're ready to build AI features.

## 2.3 Configuring AI Providers

The SDK communicates with AI providers through API keys. Each provider has its own key, configured in your `.env` file.

### Environment Variables

Add the keys for the providers you plan to use:

```ini
# Text, Images, Audio, Embeddings, Files
OPENAI_API_KEY=sk-...

# Text, Files
ANTHROPIC_API_KEY=sk-ant-...

# Text, Images, Embeddings
GEMINI_API_KEY=AIza...

# Text
XAI_API_KEY=xai-...

# Text, Embeddings
MISTRAL_API_KEY=...

# Text
OLLAMA_API_KEY=

# Embeddings, Reranking
COHERE_API_KEY=...

# Audio (TTS/STT)
ELEVENLABS_API_KEY=...

# Embeddings
JINA_API_KEY=...

# Embeddings
VOYAGEAI_API_KEY=...
```

> **Note**: You only need keys for the providers you actually use. If you're starting with OpenAI alone, that single key is sufficient. You can add more providers as your needs grow.

### The config/ai.php Configuration File

The published configuration file controls default behavior across the SDK. Here's the structure:

```php
<?php

return [

    'defaults' => [
        'text' => [
            'provider' => env('AI_TEXT_PROVIDER', 'anthropic'),
            'model' => env('AI_TEXT_MODEL'),
        ],
        'image' => [
            'provider' => env('AI_IMAGE_PROVIDER', 'openai'),
            'model' => env('AI_IMAGE_MODEL'),
        ],
        'audio' => [
            'provider' => env('AI_AUDIO_PROVIDER', 'openai'),
            'model' => env('AI_AUDIO_MODEL'),
        ],
        'transcription' => [
            'provider' => env('AI_TRANSCRIPTION_PROVIDER', 'openai'),
            'model' => env('AI_TRANSCRIPTION_MODEL'),
        ],
        'embedding' => [
            'provider' => env('AI_EMBEDDING_PROVIDER', 'openai'),
            'model' => env('AI_EMBEDDING_MODEL'),
        ],
    ],

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
        ],
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
        ],
        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],
        // ... additional providers
    ],

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

];
```

The `defaults` section determines which provider handles each type of operation when you don't specify one explicitly. The `providers` section maps driver names to API credentials. The `caching` section controls embedding cache behavior.

### Custom Base URLs for Proxy Services

If you route API traffic through a proxy service — such as LiteLLM, an Azure OpenAI Gateway, or OpenRouter — you can override the base URL for any provider:

```php
'providers' => [
    'openai' => [
        'driver' => 'openai',
        'key' => env('OPENAI_API_KEY'),
        'url' => env('OPENAI_BASE_URL'),
    ],
    'anthropic' => [
        'driver' => 'anthropic',
        'key' => env('ANTHROPIC_API_KEY'),
        'url' => env('ANTHROPIC_BASE_URL'),
    ],
],
```

Then in your `.env`:

```ini
OPENAI_BASE_URL=https://my-proxy.example.com/v1
ANTHROPIC_BASE_URL=https://my-proxy.example.com/anthropic
```

Custom base URLs are supported for: OpenAI, Anthropic, Gemini, Groq, Cohere, DeepSeek, xAI, and OpenRouter.

## 2.4 Your First AI Interaction

With the SDK installed and at least one API key configured, let's make your first AI call. The fastest way is with the `agent()` helper function — an anonymous agent that requires no class definition.

### The Anonymous Agent Helper

The `agent()` function creates an inline agent for quick, ad-hoc AI interactions:

```php
use function Laravel\Ai\{agent};

$response = agent(
    instructions: 'You are a helpful assistant who speaks concisely.',
)->prompt('What is Laravel?');

echo $response;
// "Laravel is a PHP web application framework known for its elegant
//  syntax, rich feature set, and developer-friendly tooling."
```

That's the entire integration. No class to create, no configuration to fiddle with. The `agent()` function accepts optional parameters for `instructions`, `messages`, `tools`, and `schema`, making it perfect for prototyping and one-off tasks.

### Try It in Tinker

The easiest way to experiment is through Laravel's Tinker REPL:

```bash
$ php artisan tinker
```

```php
>>> use function Laravel\Ai\{agent};
>>> $response = agent()->prompt('Write a haiku about PHP');
>>> echo $response;
```

### Try It in a Route

For a quick proof-of-concept, add a route to `routes/web.php`:

```php
use function Laravel\Ai\{agent};

Route::get('/ai-test', function () {
    $response = agent(
        instructions: 'You are a Laravel expert. Answer concisely.',
    )->prompt('What are the three best features of Laravel 12?');

    return response()->json([
        'answer' => (string) $response,
    ]);
});
```

Visit `/ai-test` in your browser, and you'll see a JSON response from the AI provider.

### Specifying a Provider and Model

By default, the SDK uses the provider and model defined in `config/ai.php`. You can override these per-call:

```php
use Laravel\Ai\Enums\Lab;

$response = agent(
    instructions: 'You are a helpful assistant.',
)->prompt(
    'Explain quantum computing in one paragraph.',
    provider: Lab::OpenAI,
    model: 'gpt-4o',
    timeout: 60,
);
```

The `Lab` enum provides type-safe provider references, preventing typos and enabling IDE autocompletion.

## 2.5 Understanding the Provider Ecosystem

One of the SDK's greatest strengths is its multi-provider architecture. Rather than locking you into a single vendor, it provides a unified API across every major AI provider — and handles the protocol differences behind the scenes.

### The Provider Feature Matrix

Not every provider supports every capability. Here's the complete matrix as of v0.2.1:

| Feature | Supported Providers |
|---------|-------------------|
| **Text Generation** | OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama |
| **Image Generation** | OpenAI, Gemini, xAI |
| **Text-to-Speech** | OpenAI, ElevenLabs |
| **Speech-to-Text** | OpenAI, ElevenLabs, Mistral |
| **Embeddings** | OpenAI, Gemini, Azure, Cohere, Mistral, Jina, VoyageAI |
| **Reranking** | Cohere, Jina |
| **File Storage** | OpenAI, Anthropic, Gemini |

This matrix will grow as new providers are integrated. The key takeaway: **text generation has the broadest support** (nine providers), while specialized capabilities like reranking are available from fewer vendors. Plan your provider strategy based on which features your application needs.

### The Lab Enum

Every provider is represented by the `Lab` enum, which you'll use throughout your code:

```php
use Laravel\Ai\Enums\Lab;

Lab::OpenAI;       // OpenAI (GPT, DALL-E, Whisper, TTS)
Lab::Anthropic;    // Anthropic (Claude)
Lab::Gemini;       // Google (Gemini)
Lab::Azure;        // Azure OpenAI Service
Lab::Groq;         // Groq (fast inference)
Lab::xAI;          // xAI (Grok)
Lab::DeepSeek;     // DeepSeek
Lab::Mistral;      // Mistral AI
Lab::Ollama;       // Ollama (local/self-hosted)
Lab::Cohere;       // Cohere (embeddings, reranking)
Lab::ElevenLabs;   // ElevenLabs (voice synthesis)
Lab::Jina;         // Jina AI (embeddings, reranking)
Lab::VoyageAI;     // Voyage AI (embeddings)
```

Using the enum instead of strings gives you IDE autocompletion, typo prevention, and a single source of truth for supported providers.

### Choosing Your Default Provider

For most applications, here's a practical starting point:

| Use Case | Recommended Default | Why |
|----------|-------------------|-----|
| **Text (general)** | Anthropic (Claude) | Excellent reasoning, long context, safety |
| **Text (budget)** | Groq or DeepSeek | Fast inference at lower cost |
| **Text (self-hosted)** | Ollama | Full data control, no API costs |
| **Images** | OpenAI | Mature image generation (DALL-E) |
| **Audio (TTS)** | ElevenLabs | Superior voice quality |
| **Audio (STT)** | OpenAI | Whisper is fast and accurate |
| **Embeddings** | OpenAI | Widely supported, good quality |
| **Reranking** | Cohere | Industry-leading reranking quality |

These are starting recommendations, not mandates. The beauty of the SDK's architecture is that switching providers later is a one-line change — in your config, in a PHP attribute, or in the `prompt()` call itself.

### Failover: Multi-Provider Resilience

One of the most powerful features in the SDK is automatic failover. Instead of specifying a single provider, pass an array:

```php
use Laravel\Ai\Enums\Lab;

$response = agent()->prompt(
    'Summarize this quarterly report...',
    provider: [Lab::OpenAI, Lab::Anthropic, Lab::Gemini],
);
```

If OpenAI is down or rate-limited, the SDK automatically retries with Anthropic. If Anthropic fails, it tries Gemini. This works across all features — agents, images, audio, embeddings — giving your application production-grade resilience with zero custom failover logic.

### What's Next

With your environment configured and your first AI interaction working, you're ready to dive into the heart of the SDK: **Agents**. In Part II, you'll learn to create dedicated agent classes, give them tools and memory, define structured output schemas, and test them thoroughly.

The foundation is laid. Let's build.
