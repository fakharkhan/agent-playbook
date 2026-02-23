# Part II — Agents: The Heart of the SDK

If there is one concept you take away from this entire book, let it be this: **agents are the fundamental building block of the Laravel AI SDK**. Every interaction with an AI provider — whether you are analyzing documents, generating structured data, searching the web, or holding multi-turn conversations — flows through an agent.

In Part I, you installed the SDK and sent your first prompt. In Part II, you will learn how agents actually work, how to give them memory, how to constrain their output, how to extend their capabilities with tools, and how to configure every aspect of their behavior. By the end of these five chapters, you will be able to build sophisticated AI features that feel native to your Laravel application.

---

## Chapter 3: Understanding Agents

Every AI-powered feature in your Laravel application begins with an agent. Agents are not an abstraction layered on top of the SDK — they *are* the SDK. Understanding them deeply is the single most important investment you can make.

### 3.1 What Are AI Agents?

In the broader AI ecosystem, the word "agent" has become overloaded. Some frameworks use it to mean an autonomous system that plans, reasons, and executes multi-step workflows with minimal human oversight. The Laravel AI SDK takes a more pragmatic, PHP-native approach.

An agent in the Laravel AI SDK is a **dedicated PHP class** that encapsulates four concerns:

1. **Instructions** — A system prompt that defines the agent's personality, expertise, and behavioral constraints.
2. **Conversation context** — The message history that gives the AI model the context it needs to produce relevant responses.
3. **Tools** — Callable functions that the AI model can invoke to interact with external systems, databases, or APIs.
4. **Output schema** — A JSON schema that constrains the model's response to a predictable, machine-readable structure.

Each of these concerns maps to a PHP interface. An agent can implement one, some, or all of them depending on the complexity of the task. A simple summarizer might only need instructions. A sales coaching system might need all four.

This design is deliberately familiar to Laravel developers. If you have ever built a form request with validation rules, a job class with a `handle()` method, or a notification with `toMail()` and `toDatabase()` channels, you already understand the pattern. An agent is a single-purpose class that declares what it needs and lets the framework handle the wiring.

### 3.2 The Agent Architecture in Laravel

Before creating your first agent, it helps to understand the contracts that define the agent system. The SDK provides five interfaces and two traits:

**Contracts (Interfaces)**

| Interface | Required Method | Purpose |
|---|---|---|
| `Agent` | `instructions()` | Base contract. Every agent must implement this. |
| `Conversational` | `messages()` | Provides conversation history to the model. |
| `HasTools` | `tools()` | Registers callable tools for the model. |
| `HasStructuredOutput` | `schema()` | Constrains the response to a JSON structure. |
| `HasMiddleware` | `middleware()` | Wraps prompts in pre/post-processing logic. |

**Traits**

| Trait | Purpose |
|---|---|
| `Promptable` | Provides `prompt()`, `stream()`, `queue()`, and `make()` methods. **Required on every agent.** |
| `RemembersConversations` | Automatically stores and retrieves conversation history using the database. |

The `Agent` interface is the only mandatory contract. It requires a single method — `instructions()` — that returns the system prompt as a `string` or `Stringable`. The `Promptable` trait is what gives the class the ability to actually send prompts to an AI provider. Without it, your class is just a data container.

Every other interface is opt-in. You compose exactly the capabilities your agent needs.

### 3.3 Creating Your First Agent

The SDK ships an Artisan generator that scaffolds agent classes into the `app/Ai/Agents/` directory:

```bash
php artisan make:agent SalesCoach
```

This generates a minimal agent:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class SalesCoach implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach, analyzing transcripts and providing feedback.';
    }
}
```

If you know upfront that your agent needs structured output, pass the `--structured` flag:

```bash
php artisan make:agent SalesCoach --structured
```

This scaffolds the class with the `HasStructuredOutput` interface and a skeleton `schema()` method already in place.

#### The Complete Agent Class

In production, a fully-featured agent often implements all four behavioral interfaces. Here is the complete `SalesCoach` agent that we will build upon throughout Part II:

```php
<?php

namespace App\Ai\Agents;

use App\Ai\Tools\RetrievePreviousTranscripts;
use App\Models\History;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class SalesCoach implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

    public function __construct(public User $user) {}

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach, analyzing transcripts and providing '
             . 'feedback and an overall sales strength score.';
    }

    public function messages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn ($message) => new Message($message->role, $message->content))
            ->all();
    }

    public function tools(): iterable
    {
        return [
            new RetrievePreviousTranscripts,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score' => $schema->integer()->min(1)->max(10)->required(),
        ];
    }
}
```

Let's walk through each piece:

- **`__construct(public User $user)`** — The agent accepts a `User` model as a dependency. This is plain PHP constructor promotion — nothing magical. The agent uses this to scope conversation history and tool access to a specific user.
- **`instructions()`** — Returns the system prompt. This is the single most important string in your agent. It tells the AI model who it is and how to behave. We will explore prompt engineering techniques in Chapter 7.
- **`messages()`** — Returns an iterable of `Message` objects representing prior conversation turns. Here, we query a `History` Eloquent model, reverse the results (so they are in chronological order), and map them to the SDK's `Message` value object. Chapter 4 covers this in depth.
- **`tools()`** — Returns an iterable of `Tool` instances that the AI model can call. Chapter 6 is entirely dedicated to tools.
- **`schema()`** — Defines the JSON schema that the model's response must conform to. Chapter 5 covers structured output.

### 3.4 Prompting Agents

The `Promptable` trait is the engine that powers every agent. It provides several methods for sending prompts, but the most fundamental is `prompt()`.

#### Basic Prompting

The simplest invocation is to instantiate the agent and call `prompt()`:

```php
$response = (new SalesCoach($user))->prompt('Analyze this sales transcript...');

return (string) $response;
```

The `prompt()` method sends the agent's instructions, conversation history, and your prompt text to the configured AI provider, waits for a response, and returns an `AgentResponse` object. Casting it to a string gives you the model's text output.

#### Container Resolution with `make()`

When your agent has constructor dependencies that can be resolved from Laravel's service container, use the static `make()` method:

```php
$response = SalesCoach::make(user: $user)->prompt('Analyze this sales transcript...');
```

The `make()` method calls `app()->make(SalesCoach::class, ['user' => $user])` under the hood, so any type-hinted dependencies beyond what you explicitly pass will be auto-injected. This is particularly useful when agents depend on services or repositories:

```php
class ContentAnalyzer implements Agent
{
    use Promptable;

    public function __construct(
        public User $user,
        public ContentRepository $repository,  // auto-injected
    ) {}

    public function instructions(): Stringable|string
    {
        return 'You analyze content for quality and SEO compliance.';
    }
}

// Only the User needs to be passed explicitly:
$response = ContentAnalyzer::make(user: $user)->prompt('Review this article...');
```

#### Overriding Provider, Model, and Timeout

Every call to `prompt()` accepts optional overrides for the AI provider, the specific model, and the HTTP timeout:

```php
use Laravel\Ai\Enums\Lab;

$response = (new SalesCoach($user))->prompt(
    'Analyze this sales transcript...',
    provider: Lab::Anthropic,
    model: 'claude-haiku-4-5-20251001',
    timeout: 120,
);
```

This is invaluable for A/B testing providers, using different models for different complexity levels, or extending timeouts for particularly large prompts. The overrides are per-call — they do not modify the agent's default configuration.

You can also override just one parameter while leaving the others at their defaults:

```php
$response = (new SalesCoach($user))->prompt(
    'Analyze this sales transcript...',
    timeout: 300,
);
```

### 3.5 Agent Contracts and Interfaces

Let us look more closely at what each interface contract requires and when you should reach for it.

#### The `Agent` Interface

```php
namespace Laravel\Ai\Contracts;

interface Agent
{
    public function instructions(): \Stringable|string;
}
```

This is the only mandatory interface. The `instructions()` method returns the system prompt — the text that tells the AI model how to behave. Every agent must implement this.

Tips for writing effective instructions:

- **Be specific about the role**: "You are a sales coach who analyzes cold-call transcripts" is better than "You are helpful."
- **Define constraints**: "Always respond in English. Never reveal internal pricing data."
- **Describe the expected output format in prose** even if you also use a schema: "Provide a score from 1 to 10, followed by specific, actionable feedback."

#### The `Conversational` Interface

```php
namespace Laravel\Ai\Contracts;

interface Conversational
{
    public function messages(): iterable;
}
```

Implement this when the agent needs awareness of prior conversation turns. The `messages()` method must return an iterable of `Laravel\Ai\Messages\Message` objects. Each `Message` takes a role (typically `'user'` or `'assistant'`) and content string. Chapter 4 explores this interface and the `RemembersConversations` trait in detail.

#### The `HasTools` Interface

```php
namespace Laravel\Ai\Contracts;

interface HasTools
{
    public function tools(): iterable;
}
```

Implement this when the agent needs to call external functions. The `tools()` method returns an iterable of objects implementing `Laravel\Ai\Contracts\Tool`. When the AI model determines it needs information or actions beyond its training data, it will invoke the appropriate tool. Chapter 6 covers tools extensively.

#### The `HasStructuredOutput` Interface

```php
namespace Laravel\Ai\Contracts;

interface HasStructuredOutput
{
    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array;
}
```

Implement this when you need the response in a predictable, machine-readable format rather than free-form text. The method receives a `JsonSchema` builder and returns an associative array defining the output structure. Chapter 5 is dedicated to this.

#### The `HasMiddleware` Interface

```php
namespace Laravel\Ai\Contracts;

interface HasMiddleware
{
    public function middleware(): array;
}
```

Implement this when you need to intercept, modify, or log prompts before they are sent and/or responses after they are received. Chapter 7 covers middleware in detail.

### 3.6 Anonymous Agents for Quick Prototyping

Not every AI interaction warrants a dedicated class. For quick prototyping, one-off tasks, or Artisan commands, the SDK provides an `agent()` function that creates an anonymous agent inline:

```php
use function Laravel\Ai\{agent};

$response = agent(
    instructions: 'You are an expert at software development.',
    messages: [],
    tools: [],
)->prompt('Tell me about Laravel');
```

The `agent()` function accepts the same parameters as a fully-defined agent class — instructions, messages, and tools — but without requiring you to create a file. This is perfect for:

- **Artisan commands** that perform one-off AI tasks
- **Tinker sessions** where you are exploring the SDK
- **Prototype code** that you intend to refactor into a proper agent later
- **Simple integrations** where a full class feels like overkill

Anonymous agents also support structured output via a `schema` closure:

```php
use Illuminate\Contracts\JsonSchema\JsonSchema;
use function Laravel\Ai\{agent};

$response = agent(
    instructions: 'You are a random number generator.',
    schema: fn (JsonSchema $schema) => [
        'number' => $schema->integer()->required(),
    ],
)->prompt('Generate a random number less than 100');

$number = $response['number']; // e.g., 42
```

And provider/model overrides work just as they do on class-based agents:

```php
use Laravel\Ai\Enums\Lab;
use function Laravel\Ai\{agent};

$response = agent(
    instructions: 'You summarize text concisely.',
)->prompt(
    'Summarize this article...',
    provider: Lab::Anthropic,
    model: 'claude-haiku-4-5-20251001',
);
```

> **When to graduate from anonymous agents**: If you find yourself copying the same `agent()` call across multiple files, or if the agent needs tools, middleware, or persistent conversations, create a proper class. Anonymous agents are for speed; classes are for structure.

---

## Chapter 4: Conversations and Memory

A single prompt-response exchange is useful, but the real power of AI emerges in *conversations* — multi-turn interactions where the model remembers what was said before. In this chapter, you will learn how to give your agents memory.

### 4.1 Stateless vs. Conversational Agents

By default, every agent prompt is **stateless**. When you call `prompt()`, the SDK sends the agent's instructions and your prompt text to the provider. The model has no knowledge of any previous interaction.

This is perfectly fine for many use cases:

- Summarizing a document
- Extracting structured data from text
- Classifying content
- Generating a product description

But other use cases require **conversational context**:

- A chatbot that remembers the user's name and preferences
- A sales coaching tool that builds on previous feedback
- A tutoring system that tracks what the student has already learned
- An assistant that refines its output based on follow-up instructions

For these, your agent needs the `Conversational` interface.

### 4.2 The Conversational Interface

The `Conversational` interface adds a single method to your agent:

```php
public function messages(): iterable
```

This method must return an iterable of `Laravel\Ai\Messages\Message` objects. Each message represents a single turn in the conversation — either a user message or an assistant response.

Here is a manual implementation that loads messages from an Eloquent model:

```php
<?php

namespace App\Ai\Agents;

use App\Models\History;
use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class SalesCoach implements Agent, Conversational
{
    use Promptable;

    public function __construct(public User $user) {}

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach, analyzing transcripts and providing feedback.';
    }

    public function messages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn ($message) => new Message($message->role, $message->content))
            ->all();
    }
}
```

A few things to note:

- **Ordering matters.** Messages must be in chronological order (oldest first). The example queries `latest()` then `reverse()` to get the 50 most recent messages in the correct sequence.
- **Limiting context is critical.** AI models have finite context windows. Sending thousands of messages will either fail or incur unnecessary cost. A limit of 50–100 recent messages is a reasonable starting point.
- **The `Message` value object** takes two arguments: the role (`'user'` or `'assistant'`) and the content string.

This approach gives you complete control over how conversation history is stored and retrieved. You can use any storage mechanism — Eloquent, Redis, a file, an external API — as long as `messages()` returns the right shape of data.

### 4.3 RemembersConversations: Automatic Memory

Manual conversation management works, but it requires you to build the storage, retrieval, and cleanup logic yourself. For most applications, the `RemembersConversations` trait is a simpler path.

#### Prerequisites

The trait stores conversations in two database tables that ship with the SDK. You must publish and run the migrations:

```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

This creates:

- **`agent_conversations`** — Stores conversation metadata (ID, user, agent class, timestamps).
- **`agent_conversation_messages`** — Stores individual messages (role, content, conversation foreign key).

#### Using the Trait

Replace your manual `messages()` implementation with the trait:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class SalesCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach, analyzing transcripts and providing feedback.';
    }
}
```

Notice what disappeared: the constructor, the `User` model import, the `History` model, the `messages()` method. The `RemembersConversations` trait handles all of it. It automatically stores every user prompt and assistant response in the database and loads the relevant history when continuing a conversation.

### 4.4 Starting and Continuing Conversations

With `RemembersConversations` in place, you manage conversations through two fluent methods.

#### Starting a New Conversation

```php
$response = (new SalesCoach)->forUser($user)->prompt('Hello! I have a cold call recording to review.');

$conversationId = $response->conversationId;
```

The `forUser($user)` method starts a new conversation scoped to the given user. The response object includes a `conversationId` that you must persist (in a session, a cookie, or a database column) so that subsequent messages can be routed to the same conversation.

#### Continuing an Existing Conversation

```php
$response = (new SalesCoach)
    ->continue($conversationId, as: $user)
    ->prompt('Can you focus on the objection handling at the 3-minute mark?');
```

The `continue()` method loads the full message history for the given conversation ID and appends the new prompt. The `as:` parameter ensures that only the conversation's owner can continue it — a critical security measure.

#### A Complete Controller Example

Here is how conversations typically look in a real Laravel controller:

```php
<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SalesCoach;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    public function start(Request $request)
    {
        $response = (new SalesCoach)
            ->forUser($request->user())
            ->prompt($request->input('message'));

        $request->session()->put('conversation_id', $response->conversationId);

        return response()->json([
            'reply' => (string) $response,
            'conversationId' => $response->conversationId,
        ]);
    }

    public function reply(Request $request)
    {
        $response = (new SalesCoach)
            ->continue($request->session()->get('conversation_id'), as: $request->user())
            ->prompt($request->input('message'));

        return response()->json([
            'reply' => (string) $response,
        ]);
    }
}
```

### 4.5 Designing Conversation Flows

Effective conversations require more than just storing messages. Here are principles that will serve you well:

**Keep the context window manageable.** Every message in the conversation history consumes tokens. If conversations can grow very long (hundreds of turns), consider implementing a summarization strategy: periodically ask the agent to summarize the conversation so far, then replace the older messages with the summary.

**Use instructions to set conversational expectations.** If your agent should greet the user, ask clarifying questions before giving advice, or refuse to go off-topic, say so in the instructions:

```php
public function instructions(): Stringable|string
{
    return <<<'PROMPT'
    You are a sales coach specializing in cold-call analysis.

    When the user starts a new conversation:
    1. Greet them and ask for the transcript or recording.
    2. Once provided, analyze the call in three areas: opening, objection handling, and close.
    3. Ask if they want to dive deeper into any area.

    Never discuss topics unrelated to sales coaching. Politely redirect if asked.
    PROMPT;
}
```

**Scope data access by user.** The `as: $user` parameter in `continue()` is not optional security — it is essential. Never allow one user to read or continue another user's conversation.

**Consider conversation lifecycle.** Conversations should not live forever. Implement cleanup logic — either a scheduled command that prunes old conversations, or an expiration policy in your application logic.

---

## Chapter 5: Structured Output

Free-form text responses are wonderful for chatbots and creative writing. They are terrible for feeding data into your application's business logic. When you need to store a score in a database, pass keywords to an SEO tool, or populate a form with AI-generated values, you need **structured output**.

### 5.1 Why Structured Output Matters

Consider this scenario: you ask an AI model to analyze a sales transcript and give it a score from 1 to 10. Without structured output, you might get:

> "I'd rate this call a solid 7 out of 10. The opening was strong, but the objection handling could use work..."

You now have to parse natural language to extract the number `7`. What if the model says "seven" instead of "7"? What if it says "7/10" or "around 7 to 8"? Parsing free text is fragile, error-prone, and a waste of your time.

With structured output, the model returns:

```json
{
    "score": 7,
    "feedback": "The opening was strong, but the objection handling could use work..."
}
```

You access `$response['score']` and get the integer `7`. Always. Reliably. The AI provider enforces the schema on its end, so the response is guaranteed to conform to your specified structure.

### 5.2 Defining JSON Schemas

To enable structured output, implement the `HasStructuredOutput` interface:

```php
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class SalesCoach implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach. Analyze transcripts and provide structured feedback.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score' => $schema->integer()->min(1)->max(10)->required(),
        ];
    }
}
```

The `schema()` method receives a `JsonSchema` builder instance and returns an associative array. Each key is a field name; each value is a schema type with optional constraints.

#### Available Schema Types

The `JsonSchema` builder provides the following type methods:

| Method | Type | Common Constraints |
|---|---|---|
| `$schema->string()` | String | `->required()` |
| `$schema->integer()` | Integer | `->min()`, `->max()`, `->required()` |
| `$schema->array()` | Array | `->required()` |

Each type can be marked as `->required()` to ensure the model always includes it in the response. The `integer()` type supports `->min()` and `->max()` to constrain the range of acceptable values.

### 5.3 Accessing Structured Responses

When you prompt an agent that implements `HasStructuredOutput`, the response is a `StructuredAgentResponse` object that implements `ArrayAccess`. You can access fields exactly like a PHP array:

```php
$response = (new SalesCoach($user))->prompt('Analyze this sales transcript...');

$score = $response['score'];       // int: 7
$feedback = $response['feedback'];  // string: "The opening was strong..."
```

You can also iterate over the response, convert it to an array, or use it in any context where `ArrayAccess` is accepted:

```php
$data = $response->toArray();

// Store directly in your database
SalesReview::create([
    'user_id' => $user->id,
    'score' => $response['score'],
    'feedback' => $response['feedback'],
]);
```

### 5.4 Real-World Schema Patterns

Let's look at practical schemas that go beyond simple examples.

#### E-Commerce Product Description Generator

This is one of the most common real-world applications of structured output. An e-commerce platform needs product descriptions, SEO metadata, and keywords — all in a predictable format that can be stored directly in the database:

```php
<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ProductDescriptionWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are an expert e-commerce copywriter. Given a product name, category,
        and basic attributes, generate compelling marketing copy optimized for
        both conversions and search engines.

        Write in an engaging, benefit-focused style. Keep the title under 80
        characters. The description should be 150-300 words. The meta description
        must be under 160 characters. Generate 5-10 relevant SEO keywords.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'meta_description' => $schema->string()->required(),
            'keywords' => $schema->array()->required(),
        ];
    }
}
```

Usage in a controller:

```php
$response = (new ProductDescriptionWriter)->prompt(
    "Product: Artisan Leather Messenger Bag\n"
    . "Category: Bags & Accessories\n"
    . "Material: Full-grain Italian leather\n"
    . "Colors: Cognac, Black, Olive\n"
    . "Price: $289"
);

Product::create([
    'title' => $response['title'],
    'description' => $response['description'],
    'meta_description' => $response['meta_description'],
    'keywords' => $response['keywords'],
    'price' => 28900,
]);
```

#### Content Moderation Agent

```php
public function schema(JsonSchema $schema): array
{
    return [
        'is_safe' => $schema->integer()->min(0)->max(1)->required(),
        'category' => $schema->string()->required(),
        'confidence' => $schema->integer()->min(0)->max(100)->required(),
        'explanation' => $schema->string()->required(),
    ];
}
```

#### Lead Qualification Agent

```php
public function schema(JsonSchema $schema): array
{
    return [
        'qualified' => $schema->integer()->min(0)->max(1)->required(),
        'score' => $schema->integer()->min(1)->max(100)->required(),
        'company_size' => $schema->string()->required(),
        'budget_indicator' => $schema->string()->required(),
        'next_action' => $schema->string()->required(),
        'reasoning' => $schema->string()->required(),
    ];
}
```

The pattern is consistent: define the shape of the data your application logic needs, and let the AI model fill it in. Structured output turns an AI model from a text generator into a **data generator** that plugs directly into your domain layer.

> **Tip**: When designing schemas, think about what your application code will *do* with each field. If you would not write `$response['field_name']` somewhere in your code, the field probably does not belong in the schema. Keep schemas focused on actionable data.

---

## Chapter 6: Tools — Extending Agent Capabilities

Out of the box, an AI model can only generate text based on its training data. It cannot check today's weather, query your database, call an API, or perform calculations it was not trained to do reliably. **Tools** bridge this gap by giving the model the ability to invoke PHP functions during a conversation.

When a model determines that it needs information or actions beyond its own capabilities, it generates a "tool call" — a structured request that the SDK intercepts, routes to your PHP tool class, executes, and feeds the result back to the model. The model then incorporates that result into its response.

### 6.1 Understanding AI Tools

The tool-calling flow works like this:

1. You send a prompt to the agent.
2. The SDK sends the prompt, instructions, and tool descriptions to the AI provider.
3. The model decides it needs to use a tool and returns a tool call request (instead of a text response).
4. The SDK invokes your tool's `handle()` method with the arguments the model specified.
5. The tool's return value is sent back to the model as context.
6. The model generates its final response incorporating the tool's output.

This cycle can repeat multiple times in a single prompt — the model might call several tools in sequence (or even in parallel, depending on the provider) before producing its final answer. The `#[MaxSteps]` attribute (covered in Chapter 7) controls how many tool-call cycles are allowed.

### 6.2 Creating Custom Tools

The SDK provides an Artisan generator for tools:

```bash
php artisan make:tool RandomNumberGenerator
```

This creates a class in `app/Ai/Tools/`:

```php
<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RandomNumberGenerator implements Tool
{
    public function description(): Stringable|string
    {
        return 'This tool may be used to generate cryptographically secure random numbers.';
    }

    public function handle(Request $request): Stringable|string
    {
        return (string) random_int($request['min'], $request['max']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'min' => $schema->integer()->min(0)->required(),
            'max' => $schema->integer()->required(),
        ];
    }
}
```

Every tool implements the `Tool` interface, which requires three methods:

| Method | Purpose |
|---|---|
| `description()` | A plain-English description of what the tool does. The AI model reads this to decide *when* to use the tool. Write it as if explaining the tool to a colleague. |
| `handle(Request $request)` | The PHP logic that executes when the tool is called. The `$request` is an `ArrayAccess` object containing the arguments the model sent, validated against your schema. |
| `schema(JsonSchema $schema)` | Defines the input parameters the model must provide when calling the tool. Uses the same `JsonSchema` builder as structured output. |

### 6.3 Tool Schemas and Validation

The tool's schema tells the AI model what arguments it needs to provide. The SDK validates the model's tool call against this schema before invoking `handle()`.

Here is a more complex tool that searches a database:

```php
<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchProducts implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search the product catalog by name, category, or price range. '
             . 'Returns matching products with their names, prices, and availability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Product::query();

        if ($request['name']) {
            $query->where('name', 'like', "%{$request['name']}%");
        }

        if ($request['category']) {
            $query->where('category', $request['category']);
        }

        if ($request['max_price']) {
            $query->where('price', '<=', $request['max_price'] * 100);
        }

        return $query->limit(10)->get()->toJson();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string(),
            'category' => $schema->string(),
            'max_price' => $schema->integer(),
        ];
    }
}
```

Notice that none of the fields are marked `required()`. The model can provide any combination of parameters depending on the user's query. If the user says "show me leather bags under $200," the model will call the tool with `category: "bags"` and `max_price: 200`, leaving `name` empty.

#### Registering Tools with an Agent

Tools are registered in the agent's `tools()` method:

```php
public function tools(): iterable
{
    return [
        new SearchProducts,
        new RandomNumberGenerator,
        new RetrievePreviousTranscripts,
    ];
}
```

You can register as many tools as needed. The AI model will read each tool's description and schema to determine which ones are relevant for a given prompt.

### 6.4 Provider Tools: WebSearch, WebFetch, FileSearch

In addition to custom tools that run in your PHP application, the SDK supports **provider tools** — tools that execute natively on the AI provider's infrastructure. These are particularly powerful because they do not consume your server resources and can access capabilities that would be complex to implement yourself.

#### WebSearch

The `WebSearch` provider tool allows the AI model to search the web in real time. This is essential for questions about current events, recent documentation, or any information that postdates the model's training cutoff.

**Supported providers**: Anthropic, OpenAI, Gemini

```php
use Laravel\Ai\Providers\Tools\WebSearch;

public function tools(): iterable
{
    return [
        new WebSearch,
    ];
}
```

You can configure `WebSearch` with constraints:

```php
public function tools(): iterable
{
    return [
        (new WebSearch)
            ->max(5)
            ->allow(['laravel.com', 'php.net', 'packagist.org']),
    ];
}
```

- **`max(int $results)`** — Limits the number of search results the model can retrieve.
- **`allow(array $domains)`** — Restricts searches to specific domains. This is a powerful safety measure for agents that should only reference trusted sources.

For location-aware searches (e.g., finding local businesses or region-specific information):

```php
(new WebSearch)->location(
    city: 'New York',
    region: 'NY',
    country: 'US',
);
```

#### WebFetch

The `WebFetch` provider tool allows the model to retrieve and read the content of specific web pages. While `WebSearch` finds relevant URLs, `WebFetch` actually reads them.

**Supported providers**: Anthropic, Gemini

```php
use Laravel\Ai\Providers\Tools\WebFetch;

public function tools(): iterable
{
    return [
        (new WebFetch)->max(3)->allow(['docs.laravel.com']),
    ];
}
```

Combining `WebSearch` and `WebFetch` gives the model a powerful research capability — it can search for information and then read the full content of the most relevant results:

```php
public function tools(): iterable
{
    return [
        (new WebSearch)->max(5)->allow(['laravel.com', 'php.net']),
        (new WebFetch)->max(3)->allow(['laravel.com', 'php.net']),
    ];
}
```

#### FileSearch

The `FileSearch` provider tool enables the model to search through documents stored in a vector store. This is the foundation of Retrieval-Augmented Generation (RAG), which we will cover extensively in Part IV. Here, we introduce the tool itself.

**Supported providers**: OpenAI, Gemini

```php
use Laravel\Ai\Providers\Tools\FileSearch;

public function tools(): iterable
{
    return [
        new FileSearch(stores: ['vs_abc123']),
    ];
}
```

You can search across multiple vector stores:

```php
new FileSearch(stores: ['vs_documentation', 'vs_support_tickets']);
```

FileSearch supports metadata filtering, which lets you narrow search results to documents matching specific criteria.

**Simple metadata filtering:**

```php
new FileSearch(stores: ['vs_docs'], where: [
    'author' => 'Taylor Otwell',
    'year' => 2026,
]);
```

**Complex metadata filtering with the query builder:**

```php
use Laravel\Ai\Providers\Tools\FileSearchQuery;

new FileSearch(stores: ['vs_docs'], where: fn (FileSearchQuery $query) =>
    $query->where('author', 'Taylor Otwell')
        ->whereNot('status', 'draft')
        ->whereIn('category', ['news', 'updates'])
);
```

The `FileSearchQuery` builder provides a fluent API similar to Eloquent's query builder, making complex filtering conditions feel natural to Laravel developers.

### 6.5 The Similarity Search Tool for RAG

While `FileSearch` operates on provider-hosted vector stores, the `SimilaritySearch` tool operates on your own database using PostgreSQL's pgvector extension. It enables the model to search your Eloquent models by semantic similarity.

#### Using an Eloquent Model

The simplest form points the tool at a model and its embedding column:

```php
use App\Models\Document;
use Laravel\Ai\Tools\SimilaritySearch;

public function tools(): iterable
{
    return [
        SimilaritySearch::usingModel(Document::class, 'embedding'),
    ];
}
```

When the model calls this tool, the SDK automatically generates an embedding for the search query, runs a vector similarity search against the `embedding` column of the `documents` table, and returns the matching records.

#### Customizing the Search

You can tune the search behavior with additional parameters:

```php
SimilaritySearch::usingModel(
    model: Document::class,
    column: 'embedding',
    minSimilarity: 0.7,
    limit: 10,
    query: fn ($query) => $query->where('published', true),
),
```

- **`minSimilarity`** — Minimum cosine similarity threshold. Results below this score are discarded.
- **`limit`** — Maximum number of results to return.
- **`query`** — A closure that receives the Eloquent query builder, allowing you to add additional constraints (scoping by tenant, filtering by status, etc.).

#### Custom Closure for Full Control

For advanced scenarios, you can provide a custom closure that handles the entire search logic:

```php
new SimilaritySearch(using: function (string $query) {
    return Document::query()
        ->where('user_id', $this->user->id)
        ->whereVectorSimilarTo('embedding', $query)
        ->limit(10)
        ->get();
}),
```

This gives you complete control over the query, including user scoping, complex joins, and custom result formatting.

#### Custom Tool Descriptions

By default, the SimilaritySearch tool uses a generic description. You can customize it to help the model understand when to use it:

```php
SimilaritySearch::usingModel(Document::class, 'embedding')
    ->withDescription('Search the company knowledge base for relevant policy documents and procedures.'),
```

A good description significantly improves the model's ability to choose the right tool at the right time.

### 6.6 Combining Multiple Tools

Real-world agents often need multiple tools working together. Here is an example of a customer support agent with a comprehensive toolset:

```php
<?php

namespace App\Ai\Agents;

use App\Ai\Tools\LookupOrder;
use App\Ai\Tools\SearchFAQ;
use App\Ai\Tools\CreateSupportTicket;
use App\Models\KnowledgeArticle;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

class SupportAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are a customer support agent for an e-commerce platform.

        Use the available tools to help customers:
        - Search the knowledge base first for common questions.
        - Look up order details when customers ask about their orders.
        - Search the web for shipping carrier tracking when needed.
        - Create a support ticket if you cannot resolve the issue.

        Always be polite and solution-oriented.
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(KnowledgeArticle::class, 'embedding')
                ->withDescription('Search the knowledge base for help articles and FAQs.'),
            new LookupOrder,
            new CreateSupportTicket,
            (new WebSearch)->max(3)->allow(['ups.com', 'fedex.com', 'usps.com']),
        ];
    }
}
```

The model will read the descriptions of all four tools and intelligently decide which ones to call based on the customer's question. A query about shipping status might trigger `LookupOrder` first, then `WebSearch` to check the carrier's tracking page. A general question about return policies would go straight to the knowledge base via `SimilaritySearch`.

---

## Chapter 7: Agent Configuration and Middleware

You now know how to build agents with instructions, conversations, structured output, and tools. In this chapter, you will learn how to **configure** every aspect of an agent's behavior using PHP attributes, and how to **intercept** the prompt lifecycle using middleware.

### 7.1 PHP Attributes for Agent Configuration

The Laravel AI SDK uses PHP 8 attributes to configure agents declaratively. Attributes are placed directly on the class definition, making the configuration visible at a glance.

Here is the full set of available attributes:

| Attribute | Description | Default |
|---|---|---|
| `#[Provider(Lab::Anthropic)]` | Sets the AI provider (or an array for failover) | Config default |
| `#[Model('claude-haiku-4-5-20251001')]` | Sets the specific model to use | Config default |
| `#[MaxSteps(10)]` | Maximum tool-call cycles before the model must respond | — |
| `#[MaxTokens(4096)]` | Maximum tokens in the generated response | — |
| `#[Temperature(0.7)]` | Sampling temperature (0.0 = deterministic, 1.0 = creative) | — |
| `#[Timeout(120)]` | HTTP request timeout in seconds | 60 |
| `#[UseCheapestModel]` | Uses the provider's cheapest available model | — |
| `#[UseSmartestModel]` | Uses the provider's most capable available model | — |

### 7.2 Provider, Model, Temperature, and Timeout

Let's see these attributes in action on a fully configured agent:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
#[MaxSteps(10)]
#[MaxTokens(4096)]
#[Temperature(0.7)]
#[Timeout(120)]
class SalesCoach implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach, analyzing transcripts and providing feedback.';
    }
}
```

Each attribute serves a specific purpose:

**`#[Provider]`** determines which AI provider handles the request. This is useful when different agents in your application use different providers. You can also pass an array for automatic failover:

```php
#[Provider([Lab::OpenAI, Lab::Anthropic])]
```

If OpenAI fails (rate limit, network error, outage), the SDK automatically retries with Anthropic.

**`#[Model]`** specifies the exact model within the chosen provider. This gives you precise control over the cost-performance tradeoff per agent.

**`#[Temperature]`** controls the randomness of the model's output. Lower values (0.0–0.3) produce more deterministic, focused responses — ideal for data extraction and classification. Higher values (0.7–1.0) produce more creative, varied responses — better for content generation and brainstorming.

**`#[MaxTokens]`** limits the length of the response. Use this to control costs and prevent the model from generating unnecessarily long output.

**`#[MaxSteps]`** is critical for agents with tools. Each "step" is one tool-call cycle (model calls a tool, tool returns a result, model processes it). Without a limit, a model could theoretically loop indefinitely. A value of 5–10 is reasonable for most agents.

**`#[Timeout]`** sets the HTTP request timeout in seconds. Complex prompts with multiple tool calls may need longer timeouts. The default is 60 seconds.

### 7.3 UseCheapestModel vs. UseSmartestModel

These two attributes are convenience shortcuts that let you optimize for cost or capability without hard-coding a specific model name:

```php
use Laravel\Ai\Attributes\UseCheapestModel;

#[UseCheapestModel]
class SimpleSummarizer implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You summarize text into concise bullet points.';
    }
}
```

```php
use Laravel\Ai\Attributes\UseSmartestModel;

#[UseSmartestModel]
class ComplexReasoner implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You solve complex multi-step reasoning problems.';
    }
}
```

**`#[UseCheapestModel]`** is perfect for high-volume, low-complexity tasks: summarization, classification, simple extraction. These tasks do not need the most capable model, and using a cheaper one can reduce costs by 10–50x.

**`#[UseSmartestModel]`** is for tasks where quality is paramount: complex reasoning, nuanced analysis, creative writing, or any task where a cheaper model produces noticeably worse results.

The advantage of these attributes over hard-coding a model name is **forward compatibility**. When Anthropic releases a new cheapest model or OpenAI introduces a more capable one, the SDK can update its mappings without requiring changes to your code.

### 7.4 Building Agent Middleware

Middleware lets you intercept the prompt lifecycle — running logic before a prompt is sent and/or after a response is received. This is the same pipeline concept as Laravel's HTTP middleware, applied to AI prompts.

#### Creating Middleware

```bash
php artisan make:agent-middleware LogPrompts
```

This creates a class in `app/Ai/Middleware/`:

```php
<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;

class LogPrompts
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        Log::info('Prompting agent', [
            'prompt' => $prompt->prompt,
        ]);

        return $next($prompt);
    }
}
```

The middleware signature is straightforward:

- **`$prompt`** — An `AgentPrompt` object containing the prompt text, provider, model, and all other prompt configuration.
- **`$next`** — A closure that passes the prompt to the next middleware in the pipeline (or to the AI provider if this is the last middleware).

Calling `$next($prompt)` sends the prompt onward. You can modify the prompt before calling `$next`, or skip `$next` entirely to short-circuit the request (useful for caching or rate limiting).

#### Registering Middleware on an Agent

Implement the `HasMiddleware` interface and return your middleware classes:

```php
use Laravel\Ai\Contracts\HasMiddleware;

class SalesCoach implements Agent, HasMiddleware
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach.';
    }

    public function middleware(): array
    {
        return [
            new LogPrompts,
            new EnforceRateLimit,
            new ValidateContentPolicy,
        ];
    }
}
```

Middleware executes in the order listed. In this example, `LogPrompts` runs first, then `EnforceRateLimit`, then `ValidateContentPolicy`, and finally the prompt is sent to the AI provider.

#### Post-Response Middleware with `then()`

Sometimes you need to run logic *after* the response is received — for logging usage, storing metrics, or triggering side effects. The `then()` method on the middleware response enables this:

```php
<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class LogUsage
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        $startTime = microtime(true);

        return $next($prompt)->then(function (AgentResponse $response) use ($prompt, $startTime) {
            $duration = microtime(true) - $startTime;

            Log::info('Agent response received', [
                'agent' => get_class($prompt->agent),
                'duration_ms' => round($duration * 1000),
                'prompt_length' => strlen($prompt->prompt),
                'response_length' => strlen($response->text),
            ]);
        });
    }
}
```

The `then()` callback receives the `AgentResponse` object, giving you access to the full response text, token usage, and any other metadata the provider returns.

### 7.5 Logging, Rate Limiting, and Guardrails

Middleware opens up a powerful set of patterns for production AI applications. Here are three of the most important.

#### Logging and Observability

A comprehensive logging middleware gives you visibility into every AI interaction in your application:

```php
<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class ObservabilityMiddleware
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        $requestId = str()->uuid()->toString();

        Log::info('AI prompt sent', [
            'request_id' => $requestId,
            'agent' => get_class($prompt->agent),
            'prompt' => $prompt->prompt,
        ]);

        return $next($prompt)->then(function (AgentResponse $response) use ($requestId) {
            Log::info('AI response received', [
                'request_id' => $requestId,
                'response_preview' => str($response->text)->limit(200)->toString(),
            ]);
        });
    }
}
```

#### Rate Limiting

Prevent excessive AI usage — whether from a single user hammering an endpoint or a bug causing infinite prompt loops:

```php
<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Prompts\AgentPrompt;
use RuntimeException;

class EnforceRateLimit
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        $key = 'ai-prompt:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 60)) {
            throw new RuntimeException('AI rate limit exceeded. Please try again later.');
        }

        RateLimiter::hit($key, decaySeconds: 60);

        return $next($prompt);
    }
}
```

This uses Laravel's built-in `RateLimiter` facade to enforce a per-user limit of 60 prompts per minute. You can adjust the limits per agent by parameterizing the middleware or using different keys.

#### Content Policy Guardrails

For applications that handle sensitive content, middleware can inspect prompts before they reach the AI provider and filter responses before they reach the user:

```php
<?php

namespace App\Ai\Middleware;

use Closure;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use RuntimeException;

class ContentPolicyGuard
{
    private array $blockedTerms = ['confidential', 'internal-only', 'password'];

    public function handle(AgentPrompt $prompt, Closure $next)
    {
        foreach ($this->blockedTerms as $term) {
            if (str_contains(strtolower($prompt->prompt), $term)) {
                throw new RuntimeException(
                    'Prompt contains restricted content and cannot be processed.'
                );
            }
        }

        return $next($prompt)->then(function (AgentResponse $response) {
            foreach ($this->blockedTerms as $term) {
                if (str_contains(strtolower($response->text), $term)) {
                    throw new RuntimeException(
                        'AI response contained restricted content and has been blocked.'
                    );
                }
            }
        });
    }
}
```

This is a simplified example — production guardrails would likely use a dedicated moderation model or service — but it illustrates the power of the middleware pattern. You can inspect, modify, or reject both prompts and responses at any point in the pipeline.

#### Combining Middleware

The real power emerges when you combine multiple middleware into a pipeline that handles cross-cutting concerns cleanly:

```php
public function middleware(): array
{
    return [
        new ObservabilityMiddleware,
        new EnforceRateLimit,
        new ContentPolicyGuard,
    ];
}
```

Each middleware focuses on one concern. The pipeline ensures they all execute in order, and any middleware can short-circuit the entire chain by not calling `$next()`. This separation of concerns keeps your agent classes focused on their core purpose — defining instructions, messages, tools, and schemas — while operational concerns live in reusable middleware classes.

---

## Part II Summary

Over these five chapters, you have learned the complete agent system that powers the Laravel AI SDK:

- **Chapter 3** introduced agents as PHP classes that encapsulate instructions, conversation context, tools, and output schemas. You learned the contracts, the `Promptable` trait, and the anonymous `agent()` function for quick prototyping.

- **Chapter 4** showed how to give agents memory through the `Conversational` interface and the `RemembersConversations` trait, enabling multi-turn conversations that persist across HTTP requests.

- **Chapter 5** demonstrated structured output — constraining AI responses to predictable JSON schemas that your application logic can consume directly, turning AI from a text generator into a data generator.

- **Chapter 6** explored tools — both custom PHP tools and provider-native tools like `WebSearch`, `WebFetch`, and `FileSearch` — that extend an agent's capabilities beyond its training data.

- **Chapter 7** covered configuration via PHP attributes and middleware for intercepting the prompt lifecycle, enabling logging, rate limiting, and content policy guardrails.

With this foundation, you are ready for Part III, where we explore the SDK's multimodal capabilities: generating images, synthesizing speech, transcribing audio, and handling file attachments.
