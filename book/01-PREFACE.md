# AI for Artisans — Mastering the Laravel AI SDK

> **Disclaimer**: This book is an independent, community-authored guide. It is not an official Laravel publication and is not affiliated with, endorsed by, or connected to Laravel LLC, Taylor Otwell, or the Laravel AI SDK core team. Laravel is created and maintained by Taylor Otwell and the Laravel community. The Laravel AI SDK is an official Laravel package built by Taylor Otwell and its contributors. All trademarks belong to their respective owners.

## About the Author

**Fakhar Zaman Khan** is the CEO of Soft Pyramid, where he leads the design and delivery of enterprise-grade software systems and intelligent automation solutions. He was the **first Pakistani professional officially certified in Laravel** — a proctored, advanced technical examination that validates expertise in framework internals, application architecture, security practices, database optimization, and production deployment. He is also the **first Pakistani ambassador for n8n**, the workflow automation platform.

Fakhar founded and grew **Pakistan's Laravel community**, which now includes thousands of active engineers working on production systems. Under his leadership, Soft Pyramid has delivered over 100 custom enterprise solutions for startups and Fortune 500 organizations across the United States and Pakistan, spanning Real Estate, SaaS, E-commerce, and Education Technology.

Beyond building software, Fakhar contributes to the broader technology ecosystem as a **jury member for the Pakistan Software Houses Association** and an **international judge for the World Startup Championship**. He has mentored engineers transitioning into senior, lead, and architectural roles, with many of his mentees now holding leadership positions or having founded their own technology ventures. Currently based in Dallas, Texas, he continues to support global developer communities.

When the Laravel AI SDK was announced by Taylor Otwell at Laracon India in February 2026, Fakhar recognized an opportunity to help fellow Laravel developers navigate this powerful new toolkit. This book is the result — months of studying the official documentation, experimenting with the SDK, and distilling the knowledge into a practical, comprehensive guide.

**Important**: Fakhar is not a creator or maintainer of Laravel, the Laravel AI SDK, or any of the official Laravel packages discussed in this book. Laravel is created and maintained by Taylor Otwell. Fakhar is a certified Laravel practitioner, enterprise architect, and community educator — someone who uses these tools to build real applications and is passionate about sharing what he learns with the community.

---

## Preface

In February 2026, Taylor Otwell — the creator of Laravel — unveiled the official Laravel AI SDK at Laracon India, and the PHP world changed overnight. For the first time, one of the most popular web frameworks on the planet shipped a first-party, batteries-included toolkit for building AI-native applications — agents, image generation, audio synthesis, embeddings, vector search, and more — all through the elegant, expressive API that Laravel developers know and love.

This book was born from the realization that **AI is no longer optional knowledge for web developers**. It's becoming as fundamental as understanding databases or authentication. And yet, the gap between "I know PHP" and "I can build production AI features" has felt enormous — until now.

The Laravel AI SDK — built by Taylor Otwell and a growing community of contributors — bridges that gap entirely. If you can write a Laravel controller, you can build an AI agent. If you understand Eloquent, you can query vector embeddings. If you've written a test with PHPUnit, you can test your AI features with `Agent::fake()`.

This book is your comprehensive guide to that bridge.

---

## Who This Book Is For

This book is written for:

- **Laravel developers** who want to add AI capabilities to their applications without learning Python or switching ecosystems
- **PHP engineers** curious about how agents, embeddings, RAG, and LLMs work in practice
- **Full-stack developers** building products that need intelligent features — chatbots, content generators, semantic search, voice interfaces
- **Technical leads and architects** evaluating how to integrate AI into their Laravel-based tech stack
- **Freelancers and agency developers** who want to offer AI-powered features to their clients

**Prerequisites**: You should be comfortable with Laravel basics — routes, controllers, Eloquent, migrations, queues, and testing. You do not need prior experience with AI, machine learning, or any AI provider's API.

---

## How to Read This Book

The book is structured in seven parts, designed to be read sequentially or used as a reference:

**Part I (Chapters 1–2)** lays the foundation. Read this if you're new to AI concepts or want to understand Laravel's AI strategy.

**Part II (Chapters 3–7)** covers Agents — the heart of the SDK. This is the most important section. Start here if you're impatient.

**Part III (Chapters 8–10)** explores multimodal capabilities: images, audio, and file handling.

**Part IV (Chapters 11–14)** dives into embeddings, semantic search, RAG, and reranking — the building blocks of intelligent applications.

**Part V (Chapters 15–18)** covers production patterns: streaming, queuing, failover, and testing.

**Part VI (Chapter 19)** introduces Laravel MCP for exposing your application to AI clients.

**Part VII (Chapters 20–22)** walks through three complete, real-world projects that tie everything together.

Each chapter builds on the previous, but **Chapters 3, 6, 11, 15, and 18** are the essential ones that every reader should study carefully.

---

## Conventions Used in This Book

Throughout this book, the following conventions are used:

**Code blocks** appear in monospace and represent complete, runnable code:

```php
$response = (new SalesCoach)->prompt('Analyze this transcript...');
```

**Terminal commands** are prefixed with `$`:

```bash
$ composer require laravel/ai
$ php artisan make:agent SalesCoach
```

**File paths** are shown relative to your Laravel project root:

```
app/Ai/Agents/SalesCoach.php
config/ai.php
```

**Important notes** appear as callouts:

> **Note**: The Laravel AI SDK requires PHP 8.4+ and Laravel 12.

**Provider references** use the `Lab` enum throughout:

```php
use Laravel\Ai\Enums\Lab;

Lab::OpenAI;
Lab::Anthropic;
Lab::Gemini;
```

---

## Acknowledgments

This book would not exist without the extraordinary work of **Taylor Otwell**, the creator of Laravel, who designed and led the development of the official Laravel AI SDK. The SDK was built by Taylor and a community of **41+ contributors** who brought it from concept to release in record time. Everything in this book documents *their* creation — I am merely a guide helping you learn to use it.

The content draws heavily from the official Laravel AI SDK documentation at [laravel.com/docs/12.x/ai-sdk](https://laravel.com/docs/12.x/ai-sdk) and the Laravel MCP documentation at [laravel.com/docs/12.x/mcp](https://laravel.com/docs/12.x/mcp). Community tutorials from developers like Tobias Schäfer, Mohammad Ali Abdul Wahed, Coder Manjeet, and the vibrant dev.to and Medium Laravel communities have also shaped the practical examples in this book.

Special thanks to the **Prism PHP** project (`prism-php/prism`), the open-source provider abstraction layer that powers the SDK under the hood, and to **Anthropic** for creating the Model Context Protocol specification.

---

*Let's build something intelligent.*
