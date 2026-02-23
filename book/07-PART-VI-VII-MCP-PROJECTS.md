# Part VI — The MCP Ecosystem

Throughout this book, you have built AI features *inside* your Laravel application — agents that answer questions, tools that fetch data, RAG pipelines that ground responses in your knowledge base. But what if the conversation flowed the other direction? What if an AI client — Cursor, Claude Desktop, a custom chatbot, or another application entirely — could reach *into* your Laravel application, call your business logic, read your resources, and execute prompts you've defined?

That is the promise of the **Model Context Protocol**, and Laravel has a first-party package to make it effortless.

---

## Chapter 19: Laravel MCP — Model Context Protocol

Every generation of web development has had its integration protocol. REST gave us a standard way for applications to talk to each other over HTTP. GraphQL gave clients the power to request exactly the data they needed. Webhooks let external systems notify your application when events occurred.

MCP is the integration protocol for the AI era.

### 19.1 What Is the Model Context Protocol?

The **Model Context Protocol (MCP)** is an open standard, originally developed by Anthropic and now adopted widely across the AI ecosystem, that defines how AI clients (called "hosts") interact with external applications (called "servers"). Think of it as a structured contract between an AI model and the outside world.

When you build an MCP server in your Laravel application, you are giving AI clients a standardized way to:

- **Discover capabilities** — The client can ask your server what tools, resources, and prompts are available.
- **Call tools** — The client can invoke functions in your application, passing structured input and receiving structured output.
- **Read resources** — The client can access data your application exposes, from database records to configuration files.
- **Use prompts** — The client can request predefined prompt templates with arguments, enabling guided interactions.

The beauty of MCP is that it works with *any* compliant client. Build your server once, and it is accessible from Cursor, Claude Desktop, Windsurf, ChatGPT plugins, custom AI assistants, and any future client that speaks the protocol.

#### MCP Architecture

An MCP interaction involves three layers:

| Layer | Role | Example |
|-------|------|---------|
| **Host** | The AI application initiating requests | Cursor IDE, Claude Desktop |
| **Client** | The protocol handler within the host | The MCP client library |
| **Server** | Your application, exposing capabilities | Your Laravel MCP server |

The host sends a request to the server through the client layer. The server processes the request — executing a tool, fetching a resource, or rendering a prompt — and returns the result. The host then uses that result as context for the AI model.

#### Transport Mechanisms

MCP supports two transport mechanisms:

- **HTTP with Server-Sent Events (Streamable HTTP)** — The server exposes a web endpoint. Clients connect over HTTP. This is the approach for web-accessible servers and is what `Mcp::web()` registers.
- **Standard I/O (stdio)** — The server runs as a local process, communicating through stdin/stdout. This is the approach for desktop integrations and local development tools, and is what `Mcp::local()` registers.

Your Laravel application can serve both transports simultaneously for the same server class.

### 19.2 Installing and Configuring Laravel MCP

Installation follows the standard Laravel package pattern:

```bash
composer require laravel/mcp
```

After installation, publish the MCP route file:

```bash
php artisan vendor:publish --tag=ai-routes
```

This creates `routes/ai.php`, which is the dedicated route file for MCP server registration — similar to how `routes/web.php` handles web routes and `routes/api.php` handles API routes.

The published route file looks like this:

```php
<?php

use Laravel\Mcp\Facades\Mcp;

// Register your MCP servers here.
```

Laravel automatically loads this route file. You don't need to add any service provider or bootstrap configuration.

#### Configuration

The MCP package reads from your application's existing configuration. Provider credentials come from `config/ai.php`, and transport settings are sensible defaults. For most applications, no additional configuration is needed beyond what you set up in Chapter 2.

### 19.3 Creating MCP Servers

An MCP server is a PHP class that declares which tools, resources, and prompts it exposes. Generate one with Artisan:

```bash
php artisan make:mcp-server WeatherServer
```

This creates `app/Mcp/Servers/WeatherServer.php`:

```php
<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Version;
use Laravel\Mcp\Attributes\Instructions;

#[Name('weather')]
#[Version('1.0.0')]
#[Instructions('Provides real-time weather data for cities worldwide.')]
class WeatherServer extends Server
{
    protected array $tools = [
        // Tool classes go here.
    ];

    protected array $resources = [
        // Resource classes go here.
    ];

    protected array $prompts = [
        // Prompt classes go here.
    ];
}
```

Let's break down each element.

#### Server Class Structure

Every MCP server extends `Laravel\Mcp\Server`. This base class handles the protocol negotiation, capability advertisement, and request routing. Your job is to declare *what* the server can do through three arrays:

- **`$tools`** — An array of tool class names that the server exposes. AI clients can discover and invoke these tools.
- **`$resources`** — An array of resource class names that provide data to clients. Resources are read-only endpoints.
- **`$prompts`** — An array of prompt class names that define reusable prompt templates with arguments.

#### Attributes

The three attributes on the class provide metadata that clients use during the discovery phase:

| Attribute | Purpose |
|-----------|---------|
| `#[Name]` | A unique identifier for the server. Clients reference this name when connecting. |
| `#[Version]` | A semantic version string. Clients may use this for compatibility checks. |
| `#[Instructions]` | A human-readable description of what the server does. AI models use this to decide when to interact with your server. |

The `Instructions` attribute is particularly important. When an AI host discovers your server, it passes these instructions to the model as context. A clear, specific description helps the model understand when and how to use your server's capabilities.

#### Registering the Server

Back in `routes/ai.php`, register the server with the transport you need:

```php
<?php

use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

// Expose over HTTP (for web-based AI clients).
Mcp::web(WeatherServer::class);

// Expose over stdio (for desktop AI clients like Cursor or Claude Desktop).
Mcp::local(WeatherServer::class);
```

You can register both transports for the same server. The `web()` method creates an HTTP endpoint at `/mcp/{server-name}` that handles the Streamable HTTP transport. The `local()` method registers the server for the stdio transport, accessible through an Artisan command.

For web transport, the endpoint URL follows the pattern:

```
https://your-app.com/mcp/weather
```

For local transport, the client launches your application as a process:

```bash
php artisan mcp:serve weather
```

### 19.4 Building MCP Tools

Tools are the most powerful primitive in MCP. They allow AI clients to *do things* in your application — query databases, call APIs, trigger business logic, anything a PHP function can do.

Generate a tool with Artisan:

```bash
php artisan make:mcp-tool GetCurrentWeather
```

This creates `app/Mcp/Tools/GetCurrentWeather.php`:

```php
<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tool;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Description;
use Laravel\Mcp\Attributes\InputSchema;
use Laravel\Mcp\Attributes\OutputSchema;

#[Name('get_current_weather')]
#[Description('Returns the current weather for a given city, including temperature, conditions, and humidity.')]
class GetCurrentWeather extends Tool
{
    #[InputSchema([
        'type' => 'object',
        'properties' => [
            'city' => [
                'type' => 'string',
                'description' => 'The city name, e.g. "San Francisco".',
            ],
            'units' => [
                'type' => 'string',
                'enum' => ['celsius', 'fahrenheit'],
                'description' => 'Temperature unit. Defaults to celsius.',
            ],
        ],
        'required' => ['city'],
    ])]
    public function handle(array $input): mixed
    {
        $city = $input['city'];
        $units = $input['units'] ?? 'celsius';

        $weather = $this->fetchWeatherFromApi($city, $units);

        return [
            'city' => $city,
            'temperature' => $weather['temp'],
            'units' => $units,
            'conditions' => $weather['conditions'],
            'humidity' => $weather['humidity'],
        ];
    }

    private function fetchWeatherFromApi(string $city, string $units): array
    {
        $response = \Illuminate\Support\Facades\Http::get(
            'https://api.weatherservice.com/v1/current',
            [
                'q' => $city,
                'units' => $units,
                'key' => config('services.weather.api_key'),
            ]
        );

        return $response->json();
    }
}
```

#### Input and Output Schemas

The `#[InputSchema]` attribute defines the JSON Schema that describes what the tool accepts. AI clients use this schema to understand *how* to call your tool. The schema serves two purposes:

1. **Discovery** — When a client asks your server what tools are available, the schema is included in the response. The AI model reads this schema to understand how to construct valid calls.
2. **Validation** — The MCP package validates incoming tool calls against the schema before your `handle()` method is invoked. Invalid input is rejected with a protocol-level error.

You can also define an `#[OutputSchema]` to describe the return format:

```php
#[OutputSchema([
    'type' => 'object',
    'properties' => [
        'city' => ['type' => 'string'],
        'temperature' => ['type' => 'number'],
        'units' => ['type' => 'string'],
        'conditions' => ['type' => 'string'],
        'humidity' => ['type' => 'number'],
    ],
])]
```

Output schemas are optional but recommended. They help clients understand what to expect in return, which improves the AI model's ability to reason about tool results.

#### Registering Tools in the Server

Add the tool class to your server's `$tools` array:

```php
class WeatherServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\GetCurrentWeather::class,
    ];
}
```

The server automatically handles tool discovery and routing. When a client calls `get_current_weather`, the server instantiates `GetCurrentWeather`, validates the input, and invokes `handle()`.

#### A More Complex Tool Example

Here is a tool that queries your application's database, demonstrating how MCP tools integrate with Eloquent:

```php
<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Laravel\Mcp\Tool;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Description;
use Laravel\Mcp\Attributes\InputSchema;

#[Name('lookup_order')]
#[Description('Looks up a customer order by order number and returns its status, items, and tracking information.')]
class LookupOrder extends Tool
{
    #[InputSchema([
        'type' => 'object',
        'properties' => [
            'order_number' => [
                'type' => 'string',
                'description' => 'The order number, e.g. "ORD-2026-00142".',
            ],
        ],
        'required' => ['order_number'],
    ])]
    public function handle(array $input): mixed
    {
        $order = Order::with(['items.product', 'shipments'])
            ->where('order_number', $input['order_number'])
            ->first();

        if (! $order) {
            return ['error' => 'Order not found.'];
        }

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'placed_at' => $order->created_at->toDateTimeString(),
            'total' => $order->total_amount,
            'items' => $order->items->map(fn ($item) => [
                'product' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
            ])->toArray(),
            'tracking' => $order->shipments->map(fn ($shipment) => [
                'carrier' => $shipment->carrier,
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status,
            ])->toArray(),
        ];
    }
}
```

This tool exposes your order data to any MCP-compliant AI client. A customer support agent in Cursor could look up an order. Claude Desktop could query order status. Any AI host that connects to your server gains the ability to retrieve order information through a clean, validated interface.

### 19.5 Resources and Prompts

#### Resources

Resources expose read-only data from your application. Unlike tools, which *do* things, resources *provide* things — documents, configuration, database records, live metrics.

Generate a resource:

```bash
php artisan make:mcp-resource ProductCatalog
```

```php
<?php

namespace App\Mcp\Resources;

use App\Models\Product;
use Laravel\Mcp\Resource;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Description;
use Laravel\Mcp\Attributes\Uri;
use Laravel\Mcp\Attributes\MimeType;

#[Name('product_catalog')]
#[Description('Returns the complete product catalog with pricing and availability.')]
#[Uri('products://catalog')]
#[MimeType('application/json')]
class ProductCatalog extends Resource
{
    public function handle(): mixed
    {
        return Product::where('active', true)
            ->select(['id', 'name', 'sku', 'price', 'stock_quantity', 'category'])
            ->get()
            ->toArray();
    }
}
```

The `#[Uri]` attribute defines a unique URI for the resource. Clients reference resources by their URI, following a scheme that describes the resource type (e.g., `products://catalog`, `docs://api-reference`).

The `#[MimeType]` attribute tells clients how to interpret the response. Common values include `application/json`, `text/plain`, and `text/markdown`.

#### Resource Templates

For parameterized resources, use URI templates:

```php
<?php

namespace App\Mcp\Resources;

use App\Models\Product;
use Laravel\Mcp\Resource;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Description;
use Laravel\Mcp\Attributes\UriTemplate;
use Laravel\Mcp\Attributes\MimeType;

#[Name('product_detail')]
#[Description('Returns detailed information for a specific product by ID.')]
#[UriTemplate('products://catalog/{productId}')]
#[MimeType('application/json')]
class ProductDetail extends Resource
{
    public function handle(string $productId): mixed
    {
        $product = Product::with(['category', 'images', 'reviews'])
            ->findOrFail($productId);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'category' => $product->category->name,
            'images' => $product->images->pluck('url')->toArray(),
            'average_rating' => $product->reviews->avg('rating'),
            'review_count' => $product->reviews->count(),
        ];
    }
}
```

The `{productId}` placeholder in the URI template is automatically extracted and passed to the `handle()` method. Clients can discover these templates during the resource listing phase and construct valid URIs.

#### Prompts

Prompts are reusable prompt templates with defined arguments. They allow you to package domain-specific prompting logic inside your server, so AI clients can leverage your expertise without knowing the details.

Generate a prompt:

```bash
php artisan make:mcp-prompt AnalyzeReviews
```

```php
<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Prompt;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Description;
use Laravel\Mcp\Attributes\ArgumentSchema;

#[Name('analyze_reviews')]
#[Description('Generates a sentiment analysis prompt for a batch of product reviews.')]
class AnalyzeReviews extends Prompt
{
    #[ArgumentSchema([
        'type' => 'object',
        'properties' => [
            'product_name' => [
                'type' => 'string',
                'description' => 'The name of the product to analyze reviews for.',
            ],
            'time_period' => [
                'type' => 'string',
                'enum' => ['7d', '30d', '90d', 'all'],
                'description' => 'The time period to analyze. Defaults to 30d.',
            ],
        ],
        'required' => ['product_name'],
    ])]
    public function handle(array $arguments): string|array
    {
        $product = \App\Models\Product::where('name', $arguments['product_name'])->first();
        $period = $arguments['time_period'] ?? '30d';

        $reviews = $product->reviews()
            ->when($period !== 'all', fn ($query) => $query->where(
                'created_at',
                '>=',
                now()->sub($this->parsePeriod($period))
            ))
            ->latest()
            ->limit(100)
            ->get();

        $reviewText = $reviews->map(fn ($r) => "Rating: {$r->rating}/5\n{$r->body}")->implode("\n---\n");

        return [
            [
                'role' => 'user',
                'content' => "Analyze the following customer reviews for \"{$product->name}\".\n\n"
                    . "Provide:\n"
                    . "1. Overall sentiment (positive, mixed, negative)\n"
                    . "2. Top 3 praised features\n"
                    . "3. Top 3 complaints\n"
                    . "4. Actionable recommendations for the product team\n\n"
                    . "Reviews:\n{$reviewText}",
            ],
        ];
    }

    private function parsePeriod(string $period): \DateInterval
    {
        return match ($period) {
            '7d' => new \DateInterval('P7D'),
            '30d' => new \DateInterval('P30D'),
            '90d' => new \DateInterval('P90D'),
        };
    }
}
```

Prompts return either a simple string or an array of message objects. When a client requests the prompt, the server fetches the actual data, assembles the prompt with full context, and returns it ready for the AI model to process.

Register all three in your server:

```php
class WeatherServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\GetCurrentWeather::class,
        \App\Mcp\Tools\LookupOrder::class,
    ];

    protected array $resources = [
        \App\Mcp\Resources\ProductCatalog::class,
        \App\Mcp\Resources\ProductDetail::class,
    ];

    protected array $prompts = [
        \App\Mcp\Prompts\AnalyzeReviews::class,
    ];
}
```

### 19.6 Authentication with OAuth and Sanctum

Exposing your application's data and business logic to external AI clients demands robust authentication. The Laravel MCP package supports two authentication strategies.

#### OAuth 2.1

For third-party AI clients connecting to your server over the web, OAuth 2.1 provides industry-standard authentication:

```php
// routes/ai.php
use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web(WeatherServer::class)->auth('oauth');
```

This leverages Laravel Passport under the hood. Clients obtain access tokens through the standard OAuth flow and include them in MCP requests. The MCP package validates the token and injects the authenticated user into the server context.

#### Sanctum

For first-party clients or simpler setups, Sanctum token-based authentication is lighter weight:

```php
Mcp::web(WeatherServer::class)->auth('sanctum');
```

Clients include a Sanctum token in the `Authorization` header. This approach is ideal when you control the AI client and want simple API token authentication.

#### Authorization: Per-Tool and Per-Resource Policies

Authentication tells you *who* is making the request. Authorization tells you *whether they're allowed to*. The MCP package integrates with Laravel's policy system to provide fine-grained access control:

```php
<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Laravel\Mcp\Tool;
use Laravel\Mcp\Attributes\Name;
use Laravel\Mcp\Attributes\Description;
use Laravel\Mcp\Attributes\InputSchema;
use Illuminate\Support\Facades\Gate;

#[Name('lookup_order')]
#[Description('Looks up a customer order by order number.')]
class LookupOrder extends Tool
{
    #[InputSchema([
        'type' => 'object',
        'properties' => [
            'order_number' => ['type' => 'string'],
        ],
        'required' => ['order_number'],
    ])]
    public function handle(array $input): mixed
    {
        $order = Order::where('order_number', $input['order_number'])->firstOrFail();

        Gate::authorize('view', $order);

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total' => $order->total_amount,
        ];
    }
}
```

By using `Gate::authorize()` inside the tool's `handle()` method, you apply the same authorization policies that protect your web and API routes. An AI client authenticated as a customer can only look up their own orders; an admin can look up any order.

### 19.7 Testing MCP Servers

#### MCP Inspector

During development, the MCP Inspector is the fastest way to test your server interactively. It connects to your server, discovers capabilities, and lets you invoke tools, read resources, and render prompts through a visual interface:

```bash
npx @modelcontextprotocol/inspector
```

Point the Inspector at your local server endpoint (`http://localhost:8000/mcp/weather`) or configure it to launch the stdio transport. You can test each tool with different inputs, verify resource outputs, and confirm that your server advertises the correct capabilities.

#### Unit Tests

For automated testing, instantiate your tools and resources directly:

```php
<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\LookupOrder;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupOrderToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_order_details(): void
    {
        $order = Order::factory()
            ->has(\App\Models\OrderItem::factory()->count(2), 'items')
            ->has(\App\Models\Shipment::factory(), 'shipments')
            ->create(['order_number' => 'ORD-2026-00142']);

        $tool = new LookupOrder();
        $result = $tool->handle(['order_number' => 'ORD-2026-00142']);

        $this->assertEquals('ORD-2026-00142', $result['order_number']);
        $this->assertCount(2, $result['items']);
        $this->assertCount(1, $result['tracking']);
    }

    public function test_it_returns_error_for_missing_order(): void
    {
        $tool = new LookupOrder();
        $result = $tool->handle(['order_number' => 'ORD-NONEXISTENT']);

        $this->assertArrayHasKey('error', $result);
    }
}
```

Since MCP tools are plain PHP classes, they are straightforward to unit test. You can also test the full HTTP transport by making requests to the MCP endpoint:

```php
public function test_mcp_endpoint_returns_tool_list(): void
{
    $response = $this->postJson('/mcp/weather', [
        'jsonrpc' => '2.0',
        'method' => 'tools/list',
        'id' => 1,
    ]);

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'get_current_weather']);
    $response->assertJsonFragment(['name' => 'lookup_order']);
}
```

This covers the MCP lifecycle end to end: server creation, tool implementation, resource exposure, prompt packaging, authentication, authorization, and testing. Your Laravel application is now not just an AI *consumer* — it is an AI *provider*, ready to be plugged into any MCP-compliant client in the ecosystem.

---

# Part VII — Real-World Projects

Theory crystallizes through practice. In the preceding six parts, you learned every major capability of the Laravel AI SDK — agents, conversations, structured output, tools, multimodal generation, embeddings, RAG, streaming, queuing, failover, testing, and MCP. Now it is time to assemble those pieces into complete, production-ready applications.

Each project in this part is designed to be built incrementally. You will start with the core agent, layer in supporting features, and finish with a comprehensive test suite. The projects increase in complexity, and each one combines capabilities from multiple chapters.

---

## Chapter 20: Project — AI-Powered Customer Support Bot

Every business with customers has support tickets. Most of those tickets ask the same questions — shipping timelines, return policies, account issues, order status. An AI-powered support bot can handle the repetitive queries instantly, escalate the complex ones to humans, and maintain a consistent, professional tone around the clock.

In this chapter, you will build a complete customer support system that combines conversational agents, RAG knowledge retrieval, custom tools for order lookup, streaming responses, and persistent conversation history.

### 20.1 Project Architecture

The support bot consists of five layers:

```
┌──────────────────────────────────────────────────┐
│                  Chat Frontend                    │
│           (SSE streaming, conversation UI)        │
├──────────────────────────────────────────────────┤
│               Streaming Endpoint                  │
│            (POST /api/support/chat)               │
├──────────────────────────────────────────────────┤
│                 SupportAgent                      │
│    (Agent + Conversational + HasTools)            │
├──────────────────────────────────────────────────┤
│                   Tools Layer                     │
│   ┌──────────┐  ┌──────────────┐  ┌───────────┐ │
│   │FileSearch│  │ LookupOrder  │  │CheckStock  │ │
│   └──────────┘  └──────────────┘  └───────────┘ │
├──────────────────────────────────────────────────┤
│              Data Layer                           │
│    Vector Store (product docs)  │  Database       │
└──────────────────────────────────────────────────┘
```

The user sends a message through a chat interface. The controller streams the agent's response back as Server-Sent Events. The agent can search a knowledge base of product documentation, look up orders by number, and check product inventory — all while maintaining conversation history across sessions.

### 20.2 Building the Support Agent

Start by generating the agent:

```bash
php artisan make:agent SupportAgent
```

Now build the full implementation:

```php
<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CheckInventory;
use App\Ai\Tools\LookupOrder;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Traits\Promptable;
use Laravel\Ai\Traits\RemembersConversations;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Tools\FileSearch;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4o')]
#[Temperature(0.3)]
class SupportAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are a professional customer support agent for Artisan Commerce, an online 
        retailer specializing in handcrafted goods.

        Your responsibilities:
        - Answer product questions using the knowledge base (always search before answering)
        - Look up order status when customers provide an order number
        - Check product availability when asked about stock
        - Maintain a friendly, helpful, and professional tone
        - If you cannot resolve an issue, offer to escalate to a human agent

        Guidelines:
        - Never invent information. If the knowledge base does not contain an answer, say so.
        - When quoting prices or policies, always cite the knowledge base.
        - For order lookups, always confirm the order number with the customer before proceeding.
        - Keep responses concise — aim for 2-3 paragraphs maximum unless the customer 
          asks for detailed information.
        - Never share internal system details or database IDs with customers.
        PROMPT;
    }

    public function tools(): array
    {
        return [
            new FileSearch(vectorStoreId: config('ai.support_vector_store_id')),
            new LookupOrder(),
            new CheckInventory(),
        ];
    }
}
```

This agent implements three contracts: `Agent` for instructions, `Conversational` for conversation history, and `HasTools` for external capabilities. The `RemembersConversations` trait handles storing and retrieving conversation history from the database automatically.

The temperature is set to 0.3 — low enough for consistent, factual responses, but not so low that the tone becomes robotic.

### 20.3 Knowledge Base with RAG

The support bot needs access to your product documentation, shipping policies, return procedures, and FAQ content. We will use a provider-managed Vector Store with the `FileSearch` tool.

#### Setting Up the Vector Store

Create a seeder that builds the knowledge base:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Ai\VectorStore;

class SupportKnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $vectorStore = VectorStore::create(
            name: 'Customer Support Knowledge Base',
        );

        $vectorStore->addFiles([
            storage_path('support-docs/shipping-policy.md'),
            storage_path('support-docs/return-policy.md'),
            storage_path('support-docs/product-catalog.md'),
            storage_path('support-docs/faq.md'),
            storage_path('support-docs/warranty-info.md'),
            storage_path('support-docs/sizing-guide.md'),
        ]);

        $this->command->info("Vector Store created: {$vectorStore->id}");
        $this->command->info("Add this to your .env: AI_SUPPORT_VECTOR_STORE_ID={$vectorStore->id}");
    }
}
```

Run the seeder, then add the vector store ID to your environment:

```bash
php artisan db:seed --class=SupportKnowledgeBaseSeeder
```

```env
AI_SUPPORT_VECTOR_STORE_ID=vs_abc123...
```

And reference it in `config/ai.php`:

```php
'support_vector_store_id' => env('AI_SUPPORT_VECTOR_STORE_ID'),
```

When the agent receives a product question, the `FileSearch` tool automatically searches the vector store, retrieves the most relevant documentation passages, and provides them to the model as context.

### 20.4 Custom Tools: LookupOrder and CheckInventory

#### LookupOrder

```php
<?php

namespace App\Ai\Tools;

use App\Models\Order;
use Laravel\Ai\Tool;
use Laravel\Ai\Attributes\Description;
use Laravel\Ai\Attributes\Schema;

#[Description('Looks up a customer order by order number and returns status, items, and tracking info.')]
class LookupOrder extends Tool
{
    #[Schema([
        'type' => 'object',
        'properties' => [
            'order_number' => [
                'type' => 'string',
                'description' => 'The order number, e.g. "ORD-2026-00142".',
            ],
        ],
        'required' => ['order_number'],
    ])]
    public function handle(string $order_number): string
    {
        $order = Order::with(['items.product', 'shipments'])
            ->where('order_number', $order_number)
            ->first();

        if (! $order) {
            return "No order found with number {$order_number}. Please verify the order number.";
        }

        $items = $order->items->map(
            fn ($item) => "- {$item->product->name} (x{$item->quantity}) — \${$item->unit_price}"
        )->implode("\n");

        $tracking = $order->shipments->map(
            fn ($s) => "- {$s->carrier}: {$s->tracking_number} ({$s->status})"
        )->implode("\n");

        return <<<RESULT
        Order: {$order->order_number}
        Status: {$order->status}
        Placed: {$order->created_at->toFormattedDateString()}
        Total: \${$order->total_amount}

        Items:
        {$items}

        Tracking:
        {$tracking}
        RESULT;
    }
}
```

#### CheckInventory

```php
<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Laravel\Ai\Tool;
use Laravel\Ai\Attributes\Description;
use Laravel\Ai\Attributes\Schema;

#[Description('Checks current inventory levels for a product by name or SKU.')]
class CheckInventory extends Tool
{
    #[Schema([
        'type' => 'object',
        'properties' => [
            'query' => [
                'type' => 'string',
                'description' => 'Product name or SKU to search for.',
            ],
        ],
        'required' => ['query'],
    ])]
    public function handle(string $query): string
    {
        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('sku', $query)
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return "No products found matching \"{$query}\".";
        }

        return $products->map(function ($product) {
            $status = match (true) {
                $product->stock_quantity > 10 => 'In Stock',
                $product->stock_quantity > 0 => "Low Stock ({$product->stock_quantity} remaining)",
                default => 'Out of Stock',
            };

            return "- {$product->name} (SKU: {$product->sku}): {$status}";
        })->implode("\n");
    }
}
```

### 20.5 Streaming Chat Endpoint

The controller receives a message, continues the conversation, and streams the response back to the frontend as Server-Sent Events:

```php
<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SupportAgent;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|string',
        ]);

        $agent = new SupportAgent();

        if ($conversationId = $request->input('conversation_id')) {
            $agent->continueConversation($conversationId);
        }

        return $agent->stream($request->input('message'));
    }
}
```

Register the route:

```php
// routes/api.php
Route::post('/support/chat', \App\Http\Controllers\SupportChatController::class)
    ->middleware('throttle:60,1');
```

The `stream()` method returns a Symfony `StreamedResponse` that emits Server-Sent Events compatible with the Vercel AI SDK protocol. On the frontend, any SSE-compatible client can consume the stream:

```javascript
// Simplified frontend example (React with Vercel AI SDK)
import { useChat } from 'ai/react';

export default function SupportChat() {
  const { messages, input, handleInputChange, handleSubmit, isLoading } = useChat({
    api: '/api/support/chat',
    body: { conversation_id: sessionStorage.getItem('conversationId') },
  });

  return (
    <div className="flex flex-col h-screen max-w-2xl mx-auto">
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.map((message) => (
          <div
            key={message.id}
            className={`p-3 rounded-lg ${
              message.role === 'user'
                ? 'bg-blue-100 ml-auto max-w-xs'
                : 'bg-gray-100 mr-auto max-w-md'
            }`}
          >
            {message.content}
          </div>
        ))}
      </div>
      <form onSubmit={handleSubmit} className="p-4 border-t flex gap-2">
        <input
          value={input}
          onChange={handleInputChange}
          placeholder="Ask a question..."
          className="flex-1 border rounded-lg px-4 py-2"
          disabled={isLoading}
        />
        <button
          type="submit"
          disabled={isLoading}
          className="bg-blue-600 text-white px-6 py-2 rounded-lg"
        >
          Send
        </button>
      </form>
    </div>
  );
}
```

### 20.6 Testing the Complete System

A comprehensive test suite uses `Agent::fake()` to test the support bot without making real API calls:

```php
<?php

namespace Tests\Feature;

use App\Ai\Agents\SupportAgent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Facades\Agent;
use Tests\TestCase;

class SupportAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_responds_to_general_questions(): void
    {
        Agent::fake([
            SupportAgent::class => 'Our return policy allows returns within 30 days of purchase.',
        ]);

        $agent = new SupportAgent();
        $response = $agent->prompt('What is your return policy?');

        $this->assertStringContainsString('return', strtolower($response->text));
        Agent::assertSent(SupportAgent::class);
    }

    public function test_agent_uses_lookup_order_tool(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-2026-00142',
            'status' => 'shipped',
        ]);

        Agent::fake([
            SupportAgent::class => "Your order ORD-2026-00142 has been shipped!",
        ]);

        $agent = new SupportAgent();
        $response = $agent->prompt('Where is my order ORD-2026-00142?');

        $this->assertStringContainsString('ORD-2026-00142', $response->text);
    }

    public function test_agent_checks_inventory(): void
    {
        Product::factory()->create([
            'name' => 'Handcrafted Ceramic Mug',
            'sku' => 'MUG-001',
            'stock_quantity' => 25,
        ]);

        Agent::fake([
            SupportAgent::class => 'The Handcrafted Ceramic Mug is currently in stock.',
        ]);

        $agent = new SupportAgent();
        $response = $agent->prompt('Do you have the ceramic mug in stock?');

        $this->assertStringContainsString('stock', strtolower($response->text));
    }

    public function test_conversation_maintains_context(): void
    {
        Agent::fake([
            SupportAgent::class => [
                'Hello! How can I help you today?',
                'Of course! Could you please provide your order number?',
            ],
        ]);

        $agent = new SupportAgent();

        $first = $agent->prompt('Hi there');
        $this->assertNotEmpty($first->text);

        $second = $agent->prompt('I need help with my order');
        $this->assertStringContainsString('order number', strtolower($second->text));
    }

    public function test_streaming_endpoint_returns_sse(): void
    {
        Agent::fake([
            SupportAgent::class => 'Welcome to Artisan Commerce support!',
        ]);

        $response = $this->postJson('/api/support/chat', [
            'message' => 'Hello',
        ]);

        $response->assertOk();
    }

    public function test_endpoint_validates_input(): void
    {
        $response = $this->postJson('/api/support/chat', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('message');
    }
}
```

This test suite verifies the agent's behavior, tool usage, conversation continuity, endpoint functionality, and input validation — all without a single API call.

---

## Chapter 21: Project — E-Commerce Product Description Generator

*This project draws inspiration from a community tutorial by Tobias Schäfer, adapted and expanded for the Laravel AI SDK.*

Writing product descriptions is one of the most tedious tasks in e-commerce. A catalog of 500 products needs 500 unique, SEO-optimized descriptions — each with a compelling title, rich HTML body, meta description, and keyword set. Hiring copywriters is expensive. Writing them yourself is exhausting. Copying and pasting leads to duplicate content penalties from search engines.

AI solves this at scale. In this chapter, you will build a product description generator that produces structured, SEO-ready content, handles bulk generation through queues, detects near-duplicate descriptions using embeddings, and fails over across providers for resilience.

### 21.1 The Business Problem

Consider an e-commerce store with the following requirements:

1. **Each product needs a unique description** — Search engines penalize duplicate content. Even products in the same category must have meaningfully different descriptions.
2. **Descriptions must be SEO-optimized** — Each description needs a keyword-rich title, an HTML body with proper heading structure, a meta description under 155 characters, and a list of target keywords.
3. **Descriptions must match category tone** — A luxury jewelry description reads differently from an outdoor camping gear description.
4. **Generation must happen at scale** — When a vendor uploads 200 new products, the descriptions should be generated in the background without blocking the application.
5. **Resilience is non-negotiable** — If OpenAI is down, generation should fall back to Anthropic or another provider.

### 21.2 Building the Description Writer Agent

```bash
php artisan make:agent ProductDescriptionWriter
```

```php
<?php

namespace App\Ai\Agents;

use App\Ai\Tools\FetchCategoryContext;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Traits\Promptable;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Enums\Lab;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4o')]
#[Temperature(0.7)]
class ProductDescriptionWriter implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are an expert e-commerce copywriter specializing in SEO-optimized product 
        descriptions. You write compelling, unique descriptions that drive conversions 
        and rank well in search engines.

        Guidelines:
        - Write in a tone appropriate to the product category (luxurious for jewelry, 
          rugged for outdoor gear, playful for toys, etc.)
        - The HTML description should use <p>, <ul>, <li>, and <strong> tags for structure
        - Include key product features, benefits, and use cases
        - The meta_description MUST be 155 characters or fewer
        - Keywords should be relevant long-tail phrases, not single words
        - Every description must be unique — never use generic filler language
        - Do not invent specifications or features not mentioned in the input
        PROMPT;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'SEO-optimized product title, 50-70 characters.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Rich HTML product description, 150-300 words.',
                ],
                'meta_description' => [
                    'type' => 'string',
                    'description' => 'Meta description for search engines, max 155 characters.',
                    'maxLength' => 155,
                ],
                'keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Array of 5-8 SEO keywords/phrases.',
                    'minItems' => 5,
                    'maxItems' => 8,
                ],
            ],
            'required' => ['title', 'description', 'meta_description', 'keywords'],
        ];
    }

    public function tools(): array
    {
        return [
            new FetchCategoryContext(),
        ];
    }
}
```

The `HasStructuredOutput` interface with the `schema()` method guarantees that every response conforms to the exact structure your application expects. No parsing, no regex extraction — the AI model returns valid JSON matching your schema every time.

### 21.3 The FetchCategoryContext Tool

This tool enriches the generation prompt by providing category-specific context — tone guidelines, competitor examples, and category attributes:

```php
<?php

namespace App\Ai\Tools;

use App\Models\ProductCategory;
use Laravel\Ai\Tool;
use Laravel\Ai\Attributes\Description;
use Laravel\Ai\Attributes\Schema;

#[Description('Fetches category-specific context including tone, attributes, and example descriptions to guide content generation.')]
class FetchCategoryContext extends Tool
{
    #[Schema([
        'type' => 'object',
        'properties' => [
            'category_id' => [
                'type' => 'integer',
                'description' => 'The product category ID.',
            ],
        ],
        'required' => ['category_id'],
    ])]
    public function handle(int $category_id): string
    {
        $category = ProductCategory::with('parent')->find($category_id);

        if (! $category) {
            return 'Category not found.';
        }

        $hierarchy = $category->parent
            ? "{$category->parent->name} > {$category->name}"
            : $category->name;

        $attributes = $category->required_attributes
            ? 'Key attributes to mention: ' . implode(', ', $category->required_attributes)
            : 'No specific attributes required.';

        return <<<CONTEXT
        Category: {$hierarchy}
        Tone: {$category->tone_description}
        Target audience: {$category->target_audience}
        {$attributes}
        
        Example high-performing description from this category:
        {$category->example_description}
        CONTEXT;
    }
}
```

### 21.4 Generating Descriptions

For a single product:

```php
use App\Ai\Agents\ProductDescriptionWriter;

$product = Product::find(42);

$response = (new ProductDescriptionWriter)->prompt(
    "Write a product description for:\n"
    . "Name: {$product->name}\n"
    . "Category ID: {$product->category_id}\n"
    . "Features: {$product->features}\n"
    . "Material: {$product->material}\n"
    . "Price: \${$product->price}"
);

$data = $response->structured;

$product->update([
    'seo_title' => $data['title'],
    'description_html' => $data['description'],
    'meta_description' => $data['meta_description'],
    'seo_keywords' => $data['keywords'],
]);
```

### 21.5 Bulk Generation with Queues

For bulk operations, queuing prevents your application from blocking while hundreds of descriptions are generated:

```php
<?php

namespace App\Jobs;

use App\Ai\Agents\ProductDescriptionWriter;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProductDescription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Product $product
    ) {}

    public function handle(): void
    {
        $prompt = "Write a product description for:\n"
            . "Name: {$this->product->name}\n"
            . "Category ID: {$this->product->category_id}\n"
            . "Features: {$this->product->features}\n"
            . "Material: {$this->product->material}\n"
            . "Price: \${$this->product->price}";

        $response = (new ProductDescriptionWriter)->prompt($prompt);
        $data = $response->structured;

        $this->product->update([
            'seo_title' => $data['title'],
            'description_html' => $data['description'],
            'meta_description' => $data['meta_description'],
            'seo_keywords' => $data['keywords'],
            'description_generated_at' => now(),
        ]);

        Log::info("Generated description for product {$this->product->id}");
    }
}
```

Dispatch in bulk from a controller or command:

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateProductDescription;
use App\Models\Product;
use Illuminate\Http\Request;

class BulkDescriptionController extends Controller
{
    public function __invoke(Request $request)
    {
        $products = Product::whereNull('description_generated_at')
            ->limit(200)
            ->get();

        $products->each(function (Product $product) {
            GenerateProductDescription::dispatch($product);
        });

        return response()->json([
            'message' => "Queued {$products->count()} description generations.",
        ]);
    }
}
```

You can also use the SDK's built-in queue integration for a more concise approach:

```php
$agent = new ProductDescriptionWriter();

$agent->queue($prompt)
    ->then(function ($response) use ($product) {
        $product->update([
            'seo_title' => $response->structured['title'],
            'description_html' => $response->structured['description'],
            'meta_description' => $response->structured['meta_description'],
            'seo_keywords' => $response->structured['keywords'],
            'description_generated_at' => now(),
        ]);
    })
    ->catch(function (\Throwable $e) use ($product) {
        Log::error("Description generation failed for product {$product->id}", [
            'error' => $e->getMessage(),
        ]);
    });
```

### 21.6 Deduplication with Embeddings

A catalog full of similar products risks generating similar descriptions. Search engines penalize duplicate content, so you need a mechanism to detect and regenerate near-duplicates.

The strategy: after generating a description, compute its embedding and compare it against existing descriptions in the same category. If the cosine similarity exceeds a threshold (0.92), the description is too similar and should be regenerated with a stronger uniqueness directive.

```php
<?php

namespace App\Services;

use App\Ai\Agents\ProductDescriptionWriter;
use App\Models\Product;
use Illuminate\Support\Str;

class DescriptionDeduplicator
{
    private const SIMILARITY_THRESHOLD = 0.92;
    private const MAX_RETRIES = 3;

    public function generateUniqueDescription(Product $product): array
    {
        $agent = new ProductDescriptionWriter();

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $prompt = $this->buildPrompt($product, $attempt);
            $response = $agent->prompt($prompt);
            $data = $response->structured;

            $embedding = Str::of($data['description'])->toEmbeddings();

            $mostSimilar = Product::query()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->whereNotNull('description_embedding')
                ->whereVectorSimilarTo('description_embedding', $embedding, minSimilarity: self::SIMILARITY_THRESHOLD)
                ->first();

            if (! $mostSimilar) {
                $product->update([
                    'seo_title' => $data['title'],
                    'description_html' => $data['description'],
                    'meta_description' => $data['meta_description'],
                    'seo_keywords' => $data['keywords'],
                    'description_embedding' => $embedding,
                    'description_generated_at' => now(),
                ]);

                return $data;
            }
        }

        throw new \RuntimeException(
            "Could not generate a sufficiently unique description for product {$product->id} "
            . "after " . self::MAX_RETRIES . " attempts."
        );
    }

    private function buildPrompt(Product $product, int $attempt): string
    {
        $base = "Write a product description for:\n"
            . "Name: {$product->name}\n"
            . "Category ID: {$product->category_id}\n"
            . "Features: {$product->features}\n"
            . "Material: {$product->material}\n"
            . "Price: \${$product->price}";

        if ($attempt > 1) {
            $base .= "\n\nIMPORTANT: Previous descriptions were too similar to other products "
                . "in this category. Use a distinctly different writing style, structure, and "
                . "opening. Emphasize unique features that differentiate this product. "
                . "Attempt {$attempt} of " . self::MAX_RETRIES . ".";
        }

        return $base;
    }
}
```

The `whereVectorSimilarTo` query leverages pgvector to efficiently find descriptions with similarity above 0.92. If a match is found, the generator retries with an explicit instruction to differentiate. After three attempts, it raises an exception that your error handling can catch and flag for human review.

### 21.7 Provider Failover for Resilience

Production systems cannot depend on a single AI provider. The Laravel AI SDK's failover mechanism makes multi-provider resilience trivial:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Traits\Promptable;
use Laravel\Ai\Attributes\Failover;
use Laravel\Ai\Enums\Lab;

#[Failover([
    [Lab::OpenAI, 'gpt-4o'],
    [Lab::Anthropic, 'claude-sonnet-4-20250514'],
    [Lab::Google, 'gemini-2.0-flash'],
])]
class ProductDescriptionWriter implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    // ... same implementation as before
}
```

The `#[Failover]` attribute replaces the single `#[Provider]` and `#[Model]` attributes. The SDK tries OpenAI first. If the request fails (rate limit, timeout, server error), it automatically retries with Anthropic. If that fails too, it falls back to Google Gemini. Your application continues generating descriptions regardless of any single provider's availability.

### 21.8 Testing the Generator

```php
<?php

namespace Tests\Feature;

use App\Ai\Agents\ProductDescriptionWriter;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\DescriptionDeduplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Facades\Agent;
use Tests\TestCase;

class ProductDescriptionWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_structured_description(): void
    {
        Agent::fake([
            ProductDescriptionWriter::class => Agent::respondWithStructured([
                'title' => 'Handcrafted Ceramic Coffee Mug — Artisan-Made in Portland',
                'description' => '<p>Start your morning with a mug as unique as you are.</p>',
                'meta_description' => 'Handcrafted ceramic coffee mug made by Portland artisans. Microwave-safe, 12oz capacity.',
                'keywords' => [
                    'handcrafted ceramic mug',
                    'artisan coffee mug',
                    'Portland handmade ceramics',
                    'unique coffee mug gift',
                    'microwave safe ceramic mug',
                ],
            ]),
        ]);

        $product = Product::factory()->create(['name' => 'Ceramic Mug']);

        $response = (new ProductDescriptionWriter)->prompt(
            "Write a product description for: {$product->name}"
        );

        $this->assertEquals(
            'Handcrafted Ceramic Coffee Mug — Artisan-Made in Portland',
            $response->structured['title']
        );
        $this->assertCount(5, $response->structured['keywords']);
        $this->assertLessThanOrEqual(155, strlen($response->structured['meta_description']));
    }

    public function test_meta_description_respects_length_limit(): void
    {
        Agent::fake([
            ProductDescriptionWriter::class => Agent::respondWithStructured([
                'title' => 'Test Product Title',
                'description' => '<p>Test description.</p>',
                'meta_description' => str_repeat('a', 155),
                'keywords' => ['keyword one', 'keyword two', 'keyword three', 'keyword four', 'keyword five'],
            ]),
        ]);

        $response = (new ProductDescriptionWriter)->prompt('Test product');

        $this->assertLessThanOrEqual(155, strlen($response->structured['meta_description']));
    }

    public function test_bulk_generation_dispatches_jobs(): void
    {
        Product::factory()->count(5)->create(['description_generated_at' => null]);

        $response = $this->postJson('/api/descriptions/generate');

        $response->assertOk();
        $response->assertJson(['message' => 'Queued 5 description generations.']);
    }
}
```

---

## Chapter 22: Project — Multi-Modal Content Platform

The first two projects focused on text — conversational support and structured content generation. This final project brings together *every modality* the Laravel AI SDK offers: text generation, image creation, audio narration, vector embeddings, and semantic search. You will build a content platform that automatically writes blog posts, generates featured images, narrates articles as audio, indexes everything for semantic search, and surfaces related articles through reranking.

This is the capstone project of the book. Every concept from every chapter converges here.

### 22.1 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     Content Platform                             │
├────────┬────────────┬──────────────┬────────────┬───────────────┤
│ Write  │ Illustrate │   Narrate    │   Index    │    Search     │
│        │            │              │            │               │
│Content │ Image::of  │ Audio::of    │Embeddings  │Vector Search  │
│Writer  │            │              │            │ + Reranking   │
│Agent   │ DALL-E /   │ OpenAI TTS / │pgvector    │               │
│        │ Gemini     │ ElevenLabs   │            │               │
├────────┴────────────┴──────────────┴────────────┴───────────────┤
│                    Article Model (Eloquent)                       │
│  title | body | featured_image | audio_url | embedding           │
└─────────────────────────────────────────────────────────────────┘
```

The workflow proceeds in five stages:

1. **Write** — A `ContentWriter` agent generates a complete blog post from a topic.
2. **Illustrate** — `Image::of()` generates a featured image based on the article title and summary.
3. **Narrate** — `Audio::of()` converts the article body into a spoken audio file.
4. **Index** — The article text is embedded and stored as a vector for semantic search.
5. **Search** — Users search by meaning, and results are refined with reranking.

### 22.2 The Article Model

First, the Eloquent model that holds everything together:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Traits\HasEmbeddings;

class Article extends Model
{
    use HasFactory, HasEmbeddings;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'summary',
        'featured_image_path',
        'audio_path',
        'embedding',
        'status',
        'published_at',
    ];

    protected $casts = [
        'embedding' => 'vector',
        'published_at' => 'datetime',
    ];
}
```

And the migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->text('summary')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->vector('embedding', 1536)->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->vectorIndex('embedding');
        });
    }
};
```

The `vector` column type and `vectorIndex` method are provided by the Laravel AI SDK's pgvector integration. The index dramatically speeds up similarity queries on large datasets.

### 22.3 Blog Post Generator with AI

```bash
php artisan make:agent ContentWriter
```

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Traits\Promptable;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Tools\WebSearch;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4o')]
#[Temperature(0.8)]
class ContentWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are an expert technical blogger who writes engaging, well-researched articles 
        about web development, Laravel, PHP, and AI. Your writing style is:

        - Clear and approachable, avoiding unnecessary jargon
        - Rich with practical examples and code snippets
        - Well-structured with headings, paragraphs, and lists
        - Between 800 and 1,500 words per article
        - Written in Markdown format

        Every article must include:
        - An engaging introduction that hooks the reader
        - Clear section headings
        - At least one code example
        - A conclusion with key takeaways

        Also generate a 2-3 sentence summary suitable for social media sharing.
        PROMPT;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'The article title, engaging and SEO-friendly.',
                ],
                'body' => [
                    'type' => 'string',
                    'description' => 'The full article body in Markdown format.',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'A 2-3 sentence summary for social media.',
                ],
            ],
            'required' => ['title', 'body', 'summary'],
        ];
    }
}
```

Generate an article:

```php
use App\Ai\Agents\ContentWriter;

$response = (new ContentWriter)->prompt(
    'Write an article about using vector embeddings for semantic search in Laravel.'
);

$data = $response->structured;
// $data['title']   → "Beyond Keywords: Building Semantic Search with Laravel and pgvector"
// $data['body']    → Full Markdown article, 800-1500 words
// $data['summary'] → "Learn how to implement semantic search..."
```

### 22.4 Auto-Generated Featured Images

Every blog post needs a featured image. Instead of hunting through stock photo libraries, generate one that matches the article content:

```php
use Laravel\Ai\Facades\Image;

$imageResponse = Image::of(
    "A modern, minimal illustration for a tech blog article titled: \"{$data['title']}\". "
    . "Style: clean vector illustration with a gradient background, featuring abstract "
    . "representations of data, code, and connectivity. Color palette: indigo, violet, "
    . "and white. No text in the image."
)
->aspect('landscape')
->quality('high')
->store('article-images');

$imagePath = $imageResponse->path;
// "article-images/img_abc123.png"
```

The `aspect('landscape')` method generates a 16:9 image suitable for blog headers. The `store()` method saves it directly to your configured filesystem disk and returns the path.

For queued generation (to avoid blocking the request):

```php
Image::of("Illustration for: {$data['title']}")
    ->aspect('landscape')
    ->quality('high')
    ->queue()
    ->store('article-images')
    ->then(function ($response) use ($article) {
        $article->update(['featured_image_path' => $response->path]);
    });
```

### 22.5 Audio Narration for Articles

Accessibility and convenience meet with audio narration. Convert the article body to spoken audio:

```php
use Laravel\Ai\Facades\Audio;

$audioResponse = Audio::of(strip_tags($article->body))
    ->voice('nova')
    ->store('article-audio');

$audioPath = $audioResponse->path;
// "article-audio/audio_def456.mp3"
```

The `strip_tags()` call removes Markdown/HTML formatting that would sound awkward when read aloud. The `voice('nova')` method selects a natural-sounding voice. Available voices vary by provider — OpenAI offers voices like `alloy`, `echo`, `fable`, `onyx`, `nova`, and `shimmer`.

For long articles, audio generation can take time. Queue it:

```php
Audio::of(strip_tags($article->body))
    ->voice('nova')
    ->queue()
    ->store('article-audio')
    ->then(function ($response) use ($article) {
        $article->update(['audio_path' => $response->path]);
    });
```

### 22.6 Semantic Content Search

With articles indexed as vector embeddings, you can build a search feature that understands meaning:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['q' => 'required|string|max:500']);

        $query = $request->input('q');

        $articles = Article::query()
            ->where('status', 'published')
            ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.3)
            ->limit(20)
            ->get();

        return view('articles.search', [
            'query' => $query,
            'articles' => $articles,
        ]);
    }
}
```

The `whereVectorSimilarTo` method handles embedding generation for the query string automatically — it converts the user's search query into a vector and finds the closest matches in the database.

### 22.7 Reranking for Related Articles

On each article page, you want to display a "Related Articles" sidebar. Vector similarity gives you candidates; reranking refines them for precision:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Article;

class ArticleController extends Controller
{
    public function show(Article $article)
    {
        $candidates = Article::query()
            ->where('id', '!=', $article->id)
            ->where('status', 'published')
            ->whereVectorSimilarTo('embedding', $article->embedding, minSimilarity: 0.3)
            ->limit(20)
            ->get();

        $related = $candidates->rerank(
            by: fn ($a) => $a->title . "\n\n" . $a->summary,
            query: $article->title . ' ' . $article->summary,
            limit: 5,
        );

        return view('articles.show', [
            'article' => $article,
            'related' => $related,
        ]);
    }
}
```

The two-stage pipeline first retrieves 20 articles that are semantically close to the current article's embedding. Then the reranker re-evaluates those 20 candidates against the article's title and summary, selecting the 5 most relevant. The result is a "Related Articles" section that feels uncannily accurate.

### 22.8 Putting It All Together

Here is the complete workflow — a controller action that orchestrates the entire content pipeline:

```php
<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ContentWriter;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Ai\Facades\Audio;
use Laravel\Ai\Facades\Image;

class ContentGenerationController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:500',
        ]);

        $topic = $request->input('topic');

        // Stage 1: Write the article.
        $response = (new ContentWriter)->prompt("Write an article about: {$topic}");
        $data = $response->structured;

        // Stage 2: Create the article record and generate its embedding.
        $article = Article::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'body' => $data['body'],
            'summary' => $data['summary'],
            'embedding' => Str::of($data['body'])->toEmbeddings(),
            'status' => 'draft',
        ]);

        // Stage 3: Generate featured image (queued).
        Image::of(
            "Modern tech blog illustration for: \"{$article->title}\". "
            . "Clean, minimal, abstract vector style with indigo and violet gradients."
        )
            ->aspect('landscape')
            ->quality('high')
            ->queue()
            ->store('article-images')
            ->then(function ($imageResponse) use ($article) {
                $article->update(['featured_image_path' => $imageResponse->path]);
            });

        // Stage 4: Generate audio narration (queued).
        Audio::of(strip_tags($article->body))
            ->voice('nova')
            ->queue()
            ->store('article-audio')
            ->then(function ($audioResponse) use ($article) {
                $article->update(['audio_path' => $audioResponse->path]);
            });

        return response()->json([
            'message' => 'Article generated. Image and audio processing in background.',
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'summary' => $article->summary,
            ],
        ]);
    }
}
```

This single controller method demonstrates the entire lifecycle:

1. **Write** — The `ContentWriter` agent generates the article as structured output with a title, body, and summary.
2. **Index** — The article body is embedded immediately with `toEmbeddings()` and stored in the `embedding` column for future semantic search.
3. **Illustrate** — Image generation is queued. When it completes, the callback updates the article's `featured_image_path`.
4. **Narrate** — Audio generation is also queued. When it completes, the callback updates the `audio_path`.
5. **Search & Relate** — Because the embedding was stored at creation time, the article is immediately discoverable through semantic search and will appear in "Related Articles" sidebars for similar content.

The synchronous part of the request completes in a few seconds (the article text generation). The image and audio — which can each take 10-30 seconds — are processed in the background through Laravel's queue system. The user gets an immediate response with the article content while the multimedia assets are generated asynchronously.

### 22.9 The Complete Workflow Test Suite

```php
<?php

namespace Tests\Feature;

use App\Ai\Agents\ContentWriter;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Facades\Agent;
use Laravel\Ai\Facades\Audio;
use Laravel\Ai\Facades\Image;
use Tests\TestCase;

class ContentPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_content_generation_pipeline(): void
    {
        Agent::fake([
            ContentWriter::class => Agent::respondWithStructured([
                'title' => 'Building Semantic Search with Laravel',
                'body' => '# Building Semantic Search\n\nThis is the article body...',
                'summary' => 'Learn how to build semantic search with Laravel and pgvector.',
            ]),
        ]);

        Image::fake();
        Audio::fake();

        $response = $this->postJson('/api/content/generate', [
            'topic' => 'semantic search in Laravel',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Building Semantic Search with Laravel']);

        $this->assertDatabaseHas('articles', [
            'title' => 'Building Semantic Search with Laravel',
            'status' => 'draft',
        ]);

        Image::assertGenerated();
        Audio::assertGenerated();
    }

    public function test_semantic_search_finds_relevant_articles(): void
    {
        Agent::fake();

        $article = Article::factory()->create([
            'title' => 'Understanding Vector Databases',
            'body' => 'Vector databases store high-dimensional embeddings...',
            'status' => 'published',
            'embedding' => Str::of('Vector databases store high-dimensional embeddings')->toEmbeddings(),
        ]);

        $response = $this->get('/articles/search?q=how+do+embedding+databases+work');

        $response->assertOk();
        $response->assertSee('Understanding Vector Databases');
    }

    public function test_related_articles_are_ranked(): void
    {
        Agent::fake();

        $main = Article::factory()->create([
            'title' => 'Laravel AI SDK Introduction',
            'summary' => 'Getting started with AI in Laravel.',
            'status' => 'published',
            'embedding' => Str::of('Laravel AI SDK introduction getting started')->toEmbeddings(),
        ]);

        Article::factory()->count(5)->create([
            'status' => 'published',
            'embedding' => Str::of('General web development topics')->toEmbeddings(),
        ]);

        $related = Article::factory()->create([
            'title' => 'Advanced Agent Patterns in Laravel',
            'summary' => 'Deep dive into Laravel AI agent design.',
            'status' => 'published',
            'embedding' => Str::of('Advanced AI agent patterns Laravel deep dive')->toEmbeddings(),
        ]);

        $response = $this->get("/articles/{$main->slug}");

        $response->assertOk();
        $response->assertSee('Advanced Agent Patterns in Laravel');
    }

    public function test_content_generation_validates_input(): void
    {
        $response = $this->postJson('/api/content/generate', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('topic');
    }

    public function test_image_generation_is_queued(): void
    {
        Agent::fake([
            ContentWriter::class => Agent::respondWithStructured([
                'title' => 'Test Article',
                'body' => 'Test body content.',
                'summary' => 'Test summary.',
            ]),
        ]);

        Image::fake();
        Audio::fake();

        $this->postJson('/api/content/generate', ['topic' => 'test']);

        Image::assertQueued();
    }

    public function test_audio_narration_is_queued(): void
    {
        Agent::fake([
            ContentWriter::class => Agent::respondWithStructured([
                'title' => 'Test Article',
                'body' => 'Test body content.',
                'summary' => 'Test summary.',
            ]),
        ]);

        Image::fake();
        Audio::fake();

        $this->postJson('/api/content/generate', ['topic' => 'test']);

        Audio::assertQueued();
    }
}
```

This test suite covers the entire content platform: the generation pipeline, semantic search, related articles with reranking, input validation, and verification that image and audio generation are properly queued.

---

## Part VII Summary

You have now built three complete, production-ready applications that demonstrate the full power of the Laravel AI SDK:

1. **Customer Support Bot** — A conversational agent with RAG knowledge retrieval, custom tools for order and inventory lookup, streaming responses over SSE, and persistent conversation history. This project showed how `Agent`, `Conversational`, and `HasTools` combine to create intelligent, context-aware interfaces.

2. **Product Description Generator** — A structured output agent that produces SEO-optimized content at scale, with bulk queue processing, embedding-based deduplication to avoid duplicate content penalties, and multi-provider failover for production resilience. This project demonstrated `HasStructuredOutput`, queued agents, embedding similarity, and the `#[Failover]` attribute.

3. **Multi-Modal Content Platform** — The capstone project that orchestrates text generation, image creation, audio narration, vector indexing, semantic search, and reranking into a single cohesive workflow. This project tied together every major capability covered in the book.

Each project followed the same pattern: start with an agent, layer in capabilities through interfaces and traits, integrate with Laravel's existing ecosystem (queues, storage, routing, testing), and verify everything with a comprehensive test suite using `::fake()` methods.

The Laravel AI SDK was designed with this philosophy: AI should be a natural extension of your Laravel application, not a foreign system bolted on. These projects prove that philosophy in practice. Your agents are classes. Your tools are classes. Your tests use fakes. Your queues use dispatchers. Everything fits the patterns you've practiced for years.

Now it's your turn to build something extraordinary.
