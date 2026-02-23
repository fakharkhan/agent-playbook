# Part V — Real-Time AI and Production Patterns

In the previous parts, you learned how to build agents, generate images and audio, and wire up RAG pipelines with embeddings and vector stores. All of that work happened synchronously — your application sent a prompt, waited for the entire response, and then delivered it to the user.

That model works for prototypes and background tasks, but it falls apart in production. Users staring at a blank screen for eight seconds while an LLM composes a response will assume the application is broken. Rate limits from a single provider will bring your AI features to their knees. Untested AI code will ship hallucinations into production.

Part V addresses all of this. You will learn to stream responses in real time, offload heavy workloads to queues and broadcast channels, build resilience with multi-provider failover, and test every AI feature without spending a cent on API calls. These are the patterns that separate a demo from a product.

---

## Chapter 15: Streaming Responses

The difference between a good AI-powered application and a great one often comes down to a single question: *how quickly does the user see something happening?*

When a large language model generates a response, it does not compose the entire answer internally and then deliver it in one burst. It produces tokens sequentially, one after another, at a rate of roughly 50 to 150 tokens per second depending on the model and provider. Without streaming, your application waits for every token to be generated before showing anything at all. A response that takes four seconds to complete means four seconds of silence — an eternity in modern web UX.

Streaming changes this dynamic entirely. The first token arrives in milliseconds. The user sees words appearing as the model thinks, creating the visceral sense that they are conversing with an intelligent system rather than waiting for a database query to return.

### 15.1 Why Streaming Matters for UX

Perceived performance is often more important than actual performance. Research on user interfaces consistently shows that users tolerate longer total wait times when they receive progressive feedback. A chat response that takes six seconds to fully render but begins appearing after 200 milliseconds *feels* faster than a response that appears all at once after three seconds.

Streaming provides three concrete UX benefits:

1. **Reduced time-to-first-byte** — The user sees content almost immediately, eliminating the perception of a stalled application.
2. **Progressive rendering** — As tokens arrive, the interface updates continuously, creating a natural, conversational rhythm.
3. **Early cancellation** — If the model begins generating an unhelpful response, the user can cancel the request before the full response is completed, saving both time and API costs.

Beyond UX, streaming also has technical benefits. Long-running synchronous requests tie up PHP workers. Streaming returns a response object immediately and delivers content over the open connection, freeing your application to handle concurrent requests more efficiently.

### 15.2 Server-Sent Events (SSE) Streaming

The Laravel AI SDK makes streaming a one-line change. Where you would normally call `prompt()`, you call `stream()` instead. The return value is a `StreamableAgentResponse` that can be returned directly from a route — Laravel handles the SSE transport automatically.

```php
use App\Ai\Agents\SalesCoach;

Route::get('/coach/stream', function () {
    return (new SalesCoach)->stream('Analyze this sales transcript and provide feedback.');
});
```

That is the entire server-side implementation. When a browser hits this endpoint, it receives a stream of Server-Sent Events, each containing a chunk of the model's response. The connection stays open until the model finishes generating, at which point the stream closes naturally.

#### The `then()` Callback

In many applications, you need to do something *after* the stream completes — save the response to a database, log usage metrics, or trigger a follow-up action. The `then()` method lets you register a callback that fires once all tokens have been delivered:

```php
use Laravel\Ai\Responses\StreamedAgentResponse;

Route::get('/coach/stream', function () {
    return (new SalesCoach)
        ->stream('Analyze this sales transcript and provide feedback.')
        ->then(function (StreamedAgentResponse $response) {
            // The full response is now available
            $fullText = $response->text;
            $events = $response->events;
            $usage = $response->usage;

            // Save to database, log metrics, etc.
            ConversationLog::create([
                'response' => $fullText,
                'input_tokens' => $usage->inputTokens,
                'output_tokens' => $usage->outputTokens,
            ]);
        });
});
```

The `StreamedAgentResponse` object gives you access to three key properties:

| Property | Type | Description |
|---|---|---|
| `$response->text` | `string` | The complete, concatenated response text |
| `$response->events` | `array` | All individual stream events received |
| `$response->usage` | `object` | Token usage data (input and output counts) |

The `then()` callback executes server-side after the last event is sent to the client. The client has already received the full stream by this point, so any work you do here — database writes, cache invalidation, webhook dispatches — happens transparently without affecting the user's experience.

### 15.3 The Vercel AI SDK Protocol

If your frontend is built with React, Vue, Svelte, or any framework that integrates with the Vercel AI SDK, you can switch the streaming protocol from raw SSE to the Vercel Data Protocol with a single method call:

```php
Route::get('/coach/stream', function () {
    return (new SalesCoach)
        ->stream('Analyze this sales transcript and provide feedback.')
        ->usingVercelDataProtocol();
});
```

The Vercel AI SDK protocol structures stream events in a format that the `useChat()` and `useCompletion()` hooks from the `ai` npm package understand natively. This means your React component can consume Laravel-streamed AI responses with zero custom parsing:

```jsx
import { useChat } from 'ai/react';

export default function ChatInterface() {
    const { messages, input, handleInputChange, handleSubmit, isLoading } = useChat({
        api: '/coach/stream',
    });

    return (
        <div className="flex flex-col h-screen max-w-2xl mx-auto p-4">
            <div className="flex-1 overflow-y-auto space-y-4">
                {messages.map((message) => (
                    <div
                        key={message.id}
                        className={`p-4 rounded-lg ${
                            message.role === 'user'
                                ? 'bg-blue-100 ml-12'
                                : 'bg-gray-100 mr-12'
                        }`}
                    >
                        <p className="text-sm font-medium mb-1">
                            {message.role === 'user' ? 'You' : 'Coach'}
                        </p>
                        <p className="whitespace-pre-wrap">{message.content}</p>
                    </div>
                ))}
            </div>

            <form onSubmit={handleSubmit} className="mt-4 flex gap-2">
                <input
                    value={input}
                    onChange={handleInputChange}
                    placeholder="Ask your sales coach..."
                    className="flex-1 border rounded-lg px-4 py-2"
                    disabled={isLoading}
                />
                <button
                    type="submit"
                    disabled={isLoading}
                    className="bg-blue-600 text-white px-6 py-2 rounded-lg disabled:opacity-50"
                >
                    {isLoading ? 'Thinking...' : 'Send'}
                </button>
            </form>
        </div>
    );
}
```

The `useChat` hook handles the entire streaming lifecycle: it sends the user's message to your Laravel endpoint, reads the streamed response token by token, and reactively updates the `messages` array as new content arrives. You write zero parsing logic.

You can combine the Vercel Data Protocol with the `then()` callback. They are orthogonal features:

```php
Route::get('/coach/stream', function () {
    return (new SalesCoach)
        ->stream('Analyze this sales transcript and provide feedback.')
        ->usingVercelDataProtocol()
        ->then(function (StreamedAgentResponse $response) {
            ConversationLog::create(['response' => $response->text]);
        });
});
```

### 15.4 Manual Event Iteration

Sometimes you need fine-grained control over each streaming event — to transform content, filter events, or dispatch side effects as the stream progresses. The stream object is iterable, so you can loop over it directly:

```php
$stream = (new SalesCoach)->stream('Analyze this sales transcript.');

foreach ($stream as $event) {
    // Each $event contains a chunk of the response
    echo $event;

    // You could also log, transform, or broadcast each chunk
    Log::debug('Stream chunk received', ['content' => (string) $event]);
}
```

Manual iteration is particularly useful when you are building custom streaming protocols, writing CLI commands that display real-time AI output, or piping stream events into other systems. Consider an Artisan command that provides real-time coaching feedback:

```php
<?php

namespace App\Console\Commands;

use App\Ai\Agents\SalesCoach;
use Illuminate\Console\Command;

class AnalyzeTranscript extends Command
{
    protected $signature = 'coach:analyze {transcript}';
    protected $description = 'Analyze a sales transcript with real-time AI feedback';

    public function handle(): void
    {
        $transcript = file_get_contents($this->argument('transcript'));
        $stream = (new SalesCoach)->stream("Analyze this transcript:\n\n{$transcript}");

        foreach ($stream as $event) {
            $this->output->write((string) $event);
        }

        $this->newLine(2);
        $this->info('Analysis complete.');
    }
}
```

Running `php artisan coach:analyze storage/transcripts/call-042.txt` would display the AI's analysis character by character in the terminal, creating a typewriter effect that makes long analyses feel interactive.

### 15.5 Building a Chat Interface

Let us bring everything together and build a complete streaming chat interface. This example demonstrates the full pattern: a Laravel backend that streams responses, and a frontend that consumes them using the native `fetch` API with `ReadableStream`.

**The Backend Route**

```php
use App\Ai\Agents\ChatAssistant;
use Illuminate\Http\Request;

Route::post('/chat', function (Request $request) {
    $request->validate([
        'message' => 'required|string|max:2000',
        'conversation_id' => 'nullable|string',
    ]);

    $agent = new ChatAssistant;

    if ($conversationId = $request->input('conversation_id')) {
        $agent = $agent->continue($conversationId, as: $request->user());
    } else {
        $agent = $agent->forUser($request->user());
    }

    return $agent
        ->stream($request->input('message'))
        ->then(function (StreamedAgentResponse $response) use ($agent) {
            // The conversation ID is available after the stream completes
            cache()->put(
                "last_conversation:{$agent->user->id}",
                $response->conversationId,
                now()->addDay()
            );
        });
});
```

**The Frontend JavaScript**

For projects not using the Vercel AI SDK, you can consume SSE streams directly with the `fetch` API and `ReadableStream`:

```javascript
async function sendMessage(message) {
    const responseContainer = document.getElementById('response');
    responseContainer.textContent = '';

    const response = await fetch('/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            message: message,
            conversation_id: currentConversationId,
        }),
    });

    const reader = response.body.getReader();
    const decoder = new TextDecoder();

    while (true) {
        const { done, value } = await reader.read();

        if (done) break;

        const chunk = decoder.decode(value, { stream: true });

        // SSE events are formatted as "data: ...\n\n"
        const lines = chunk.split('\n');
        for (const line of lines) {
            if (line.startsWith('data: ')) {
                const data = line.slice(6);
                if (data === '[DONE]') break;
                responseContainer.textContent += data;
            }
        }
    }
}
```

Alternatively, if you prefer the native `EventSource` API for GET-based streaming endpoints:

```javascript
function streamResponse(prompt) {
    const responseContainer = document.getElementById('response');
    responseContainer.textContent = '';

    const encodedPrompt = encodeURIComponent(prompt);
    const eventSource = new EventSource(`/coach/stream?prompt=${encodedPrompt}`);

    eventSource.onmessage = function (event) {
        if (event.data === '[DONE]') {
            eventSource.close();
            return;
        }
        responseContainer.textContent += event.data;
    };

    eventSource.onerror = function () {
        eventSource.close();
        responseContainer.textContent += '\n\n[Connection lost]';
    };
}
```

The `EventSource` API handles reconnection automatically, making it a resilient choice for streaming interfaces. However, it only supports GET requests. For POST-based endpoints — which are more common in chat applications where you need to send conversation history — use `fetch` with `ReadableStream` as shown above.

---

## Chapter 16: Broadcasting and Queuing

Streaming is powerful, but it requires the user to have an active HTTP connection to your server. What happens when you need to run an AI prompt in the background — triggered by a webhook, a scheduled task, or a queue worker — and deliver the results to the user later? This is the domain of broadcasting and queuing.

Laravel's broadcasting infrastructure (Reverb, Pusher, Ably) and queue system are first-class integrations in the AI SDK. You can broadcast each streaming event over WebSockets, queue entire agent prompts for background processing, and handle both success and failure gracefully.

### 16.1 Broadcasting Streamed Events

When streaming, you can broadcast each event to a Laravel broadcasting channel as it arrives. This lets background processes stream AI responses to connected frontends in real time via WebSockets:

```php
use App\Ai\Agents\SalesCoach;
use Illuminate\Broadcasting\Channel;

$stream = (new SalesCoach)->stream('Analyze this sales transcript.');

foreach ($stream as $event) {
    $event->broadcast(new Channel('coaching'));
}
```

Each `$event` is broadcast as it arrives from the AI provider, so the WebSocket client receives tokens in real time — the same progressive rendering you get with SSE, but over a persistent WebSocket connection.

For queued broadcasting (which dispatches each event through Laravel's queue system rather than broadcasting synchronously), use `broadcastNow()` for immediate dispatch or pair with a queue-aware setup:

```php
foreach ($stream as $event) {
    $event->broadcastNow(new Channel('coaching'));
}
```

#### The `broadcastOnQueue` Shorthand

For the most common use case — streaming an agent response directly to a broadcast channel in the background — the SDK provides a dedicated method that combines streaming, broadcasting, and queuing in a single call:

```php
use App\Ai\Agents\SalesCoach;
use Illuminate\Broadcasting\Channel;

(new SalesCoach)->broadcastOnQueue(
    'Analyze this sales transcript.',
    new Channel('coaching'),
);
```

This dispatches the entire operation to your queue. A queue worker picks it up, streams the response from the AI provider, and broadcasts each token to the specified channel. Your HTTP request returns immediately.

On the frontend, you listen to the broadcast channel using Laravel Echo:

```javascript
import Echo from 'laravel-echo';

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
});

echo.channel('coaching')
    .listen('.streaming', (event) => {
        document.getElementById('response').textContent += event.content;
    });
```

This pattern is ideal for dashboard widgets, notification panels, or any scenario where the user initiates an AI request and expects to see results appear without maintaining an active HTTP connection to your application server.

#### Private and Presence Channels

For user-specific responses, use private or presence channels:

```php
use Illuminate\Broadcasting\PrivateChannel;

(new SalesCoach)->broadcastOnQueue(
    'Analyze this transcript.',
    new PrivateChannel('coaching.' . $user->id),
);
```

```javascript
echo.private(`coaching.${userId}`)
    .listen('.streaming', (event) => {
        appendToChat(event.content);
    });
```

### 16.2 Queuing Agent Prompts

Not every AI workload needs real-time delivery. Generating product descriptions in bulk, processing uploaded documents, or running nightly analytics — these are tasks where you want to offload the work to a background queue and handle the result asynchronously.

The `queue()` method dispatches the agent prompt to Laravel's queue system:

```php
use App\Ai\Agents\ProductWriter;
use Laravel\Ai\Responses\AgentResponse;

Route::post('/products/{product}/describe', function (Product $product) {
    (new ProductWriter)
        ->queue("Write a compelling product description for: {$product->name}")
        ->then(function (AgentResponse $response) use ($product) {
            $product->update([
                'ai_description' => $response->text,
                'description_generated_at' => now(),
            ]);
        })
        ->catch(function (Throwable $e) use ($product) {
            Log::error('Failed to generate description', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            Notification::send(
                $product->owner,
                new DescriptionGenerationFailed($product)
            );
        });

    return back()->with('status', 'Description generation queued.');
});
```

The `then()` callback receives the completed `AgentResponse` — exactly the same object you would get from a synchronous `prompt()` call. The `catch()` callback receives any `Throwable` that occurred during processing, whether it was an API error, a timeout, or a rate limit.

The HTTP request returns immediately after dispatching to the queue. The actual AI interaction happens in a queue worker process.

### 16.3 Background AI Processing

For heavy workloads that involve multiple AI operations, combine queuing with Laravel's job system for maximum control:

```php
<?php

namespace App\Jobs;

use App\Ai\Agents\ProductWriter;
use App\Ai\Agents\SeoAnalyzer;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Responses\AgentResponse;

class GenerateProductContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Product $product,
    ) {}

    public function handle(): void
    {
        $description = (new ProductWriter)->prompt(
            "Write a product description for: {$this->product->name}. "
            . "Category: {$this->product->category->name}. "
            . "Features: {$this->product->features}."
        );

        $this->product->update(['ai_description' => $description->text]);

        $seo = (new SeoAnalyzer)->prompt(
            "Analyze this product description for SEO and suggest improvements:\n\n"
            . $description->text
        );

        $this->product->update(['seo_suggestions' => $seo->text]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Product content generation failed', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

Dispatch this job for bulk processing:

```php
$products = Product::whereNull('ai_description')->get();

foreach ($products as $product) {
    GenerateProductContent::dispatch($product);
}
```

Laravel's queue system gives you automatic retries, backoff strategies, rate limiting, and dead letter handling — all essential for production AI workloads where API rate limits and transient errors are a fact of life.

### 16.4 Error Handling with then() and catch()

The `then()` and `catch()` callbacks on queued operations follow a pattern familiar to anyone who has worked with JavaScript promises. The critical difference is that these callbacks execute inside your queue worker, not in the original HTTP request context:

```php
(new ContentModerator)
    ->queue("Review this user-submitted content: {$content}")
    ->then(function (AgentResponse $response) use ($submission) {
        $result = json_decode($response->text, true);

        if ($result['safe']) {
            $submission->approve();
        } else {
            $submission->flag($result['reason']);
            Notification::send($submission->author, new ContentFlagged($result['reason']));
        }
    })
    ->catch(function (Throwable $e) use ($submission) {
        // AI moderation failed — fall back to manual review queue
        $submission->update(['status' => 'pending_manual_review']);

        Log::warning('AI moderation failed, falling back to manual review', [
            'submission_id' => $submission->id,
            'error' => $e->getMessage(),
        ]);
    });
```

The `catch()` callback is your safety net. In production, AI API calls can fail for many reasons: rate limits, network timeouts, invalid responses, provider outages. Always provide a `catch()` handler for queued operations. Without one, failed jobs will silently end up in your failed jobs table, and the user will never know what happened.

---

## Chapter 17: Failover and Resilience

A production AI application cannot depend on a single provider. OpenAI has outages. Anthropic has rate limits. Gemini has capacity constraints. If your application's AI features go down every time one provider hiccups, you do not have a production system — you have a prototype with a credit card attached.

The Laravel AI SDK provides multi-provider failover as a first-class feature, along with a comprehensive event system that lets you monitor every AI interaction in real time.

### 17.1 Multi-Provider Failover

Failover is activated by passing an array of providers instead of a single provider. The SDK tries the first provider, and if it fails — for any reason — it automatically falls back to the next one in the list:

```php
use App\Ai\Agents\SalesCoach;
use Laravel\Ai\Enums\Lab;

$response = (new SalesCoach)->prompt(
    'Analyze this sales transcript.',
    provider: [Lab::OpenAI, Lab::Anthropic],
);
```

If OpenAI returns an error — whether a rate limit (429), a server error (500), a timeout, or any other failure — the SDK immediately retries the same prompt with Anthropic. The calling code receives a response regardless of which provider ultimately served it. No conditional logic, no try/catch blocks, no manual retry loops.

You can chain as many providers as you need:

```php
$response = (new SalesCoach)->prompt(
    'Analyze this sales transcript.',
    provider: [Lab::OpenAI, Lab::Anthropic, Lab::Gemini],
);
```

The SDK tries each provider in order. If all three fail, the final provider's exception is thrown, giving you a clear signal that the entire failover chain has been exhausted.

#### Failover with PHP Attributes

For agents that should always use failover, configure it at the class level with the `#[Provider]` attribute:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider([Lab::OpenAI, Lab::Anthropic])]
class ResilientCoach implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a sales coach, analyzing transcripts and providing feedback.';
    }
}
```

Now every call to this agent — `prompt()`, `stream()`, `queue()` — automatically uses the failover chain. No per-call configuration needed.

#### Failover Across All Features

Failover is not limited to agents. It works with every feature in the SDK:

```php
use Laravel\Ai\Image;
use Laravel\Ai\Audio;
use Laravel\Ai\Embeddings;

// Image generation with failover
$image = Image::of('A mountain landscape at sunset')
    ->generate(provider: [Lab::Gemini, Lab::xAI]);

// Audio generation with failover
$audio = Audio::of('Welcome to our application.')
    ->generate(provider: [Lab::OpenAI, Lab::ElevenLabs]);

// Embeddings with failover
$response = Embeddings::for(['Laravel is a PHP framework.'])
    ->generate(provider: [Lab::OpenAI, Lab::Gemini]);
```

This universality means you can build a resilient application layer-by-layer, applying failover wherever the business impact of a provider outage is high.

### 17.2 Rate Limit Handling

Rate limits are the most common cause of AI API failures in production. Every provider imposes limits on requests per minute, tokens per minute, or both. When you hit a rate limit, the provider returns a 429 response.

With failover configured, rate limits are handled automatically. If OpenAI rate-limits your request, the SDK falls back to Anthropic without any intervention on your part:

```php
// If OpenAI returns 429, Anthropic handles the request
$response = (new SalesCoach)->prompt(
    'Analyze this transcript.',
    provider: [Lab::OpenAI, Lab::Anthropic],
);
```

For high-throughput applications that need more granular control, combine failover with Laravel's rate limiter and queue system:

```php
<?php

namespace App\Jobs;

use App\Ai\Agents\ProductWriter;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class GenerateDescription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120];

    public function __construct(public Product $product) {}

    public function middleware(): array
    {
        return [new RateLimited('ai-requests')];
    }

    public function handle(): void
    {
        $response = (new ProductWriter)->prompt(
            "Write a description for: {$this->product->name}",
            provider: [Lab::OpenAI, Lab::Anthropic, Lab::Gemini],
        );

        $this->product->update(['ai_description' => $response->text]);
    }
}
```

Define the rate limiter in a service provider:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('ai-requests', function (object $job) {
    return Limit::perMinute(60);
});
```

This combination gives you three layers of protection: Laravel's rate limiter prevents you from overwhelming any single provider, the SDK's failover switches to an alternate provider on rate-limit errors, and the queue's backoff strategy retries failed jobs with increasing delays.

### 17.3 Graceful Degradation Patterns

Resilient applications do not just fail over to another provider — they degrade gracefully when all providers are unavailable. The user should always get *something*, even if that something is a cached response or a polite message explaining the situation.

#### Pattern: Cached Fallback

```php
use App\Ai\Agents\ProductRecommender;
use Illuminate\Support\Facades\Cache;

function getRecommendations(User $user): array
{
    $cacheKey = "recommendations:{$user->id}";

    try {
        $response = (new ProductRecommender)->prompt(
            "Recommend products for user preferences: {$user->preferences}",
            provider: [Lab::OpenAI, Lab::Anthropic],
        );

        $recommendations = json_decode($response->text, true);

        Cache::put($cacheKey, $recommendations, now()->addHours(6));

        return $recommendations;
    } catch (Throwable $e) {
        Log::warning('AI recommendations failed, serving cache', [
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);

        return Cache::get($cacheKey, default: [
            ['name' => 'Popular Items', 'items' => Product::popular()->limit(5)->get()],
        ]);
    }
}
```

#### Pattern: Feature Flag Degradation

```php
use App\Ai\Agents\ContentSummarizer;

function summarizeArticle(Article $article): string
{
    if (! config('features.ai_summarization', true)) {
        return Str::limit($article->body, 200);
    }

    try {
        return (new ContentSummarizer)
            ->prompt("Summarize: {$article->body}")
            ->text;
    } catch (Throwable) {
        return Str::limit($article->body, 200);
    }
}
```

The key principle is that your application should never show an unhandled error page because an AI provider is down. AI features should be additive — they enhance the experience when available and fall back to simpler alternatives when they are not.

### 17.4 Monitoring AI Usage with Events

The Laravel AI SDK dispatches events at every stage of every AI operation. These events integrate with Laravel's event system, so you can listen for them using standard event listeners, subscribers, or closures.

Here is the complete list of events the SDK dispatches:

| Event | Triggered When |
|---|---|
| `PromptingAgent` | Before an agent prompt is sent |
| `AgentPrompted` | After an agent completes a prompt |
| `StreamingAgent` | Before agent streaming starts |
| `AgentStreamed` | After an agent completes streaming |
| `GeneratingImage` | Before image generation starts |
| `ImageGenerated` | After image generation completes |
| `GeneratingAudio` | Before audio generation starts |
| `AudioGenerated` | After audio generation completes |
| `GeneratingTranscription` | Before transcription starts |
| `TranscriptionGenerated` | After transcription completes |
| `GeneratingEmbeddings` | Before embeddings generation starts |
| `EmbeddingsGenerated` | After embeddings generation completes |
| `Reranking` | Before reranking starts |
| `Reranked` | After reranking completes |
| `InvokingTool` | Before a tool is invoked |
| `ToolInvoked` | After a tool is invoked |
| `StoringFile` | Before a file is stored |
| `FileStored` | After a file is stored |
| `FileDeleted` | After a file is deleted |
| `CreatingStore` | Before creating a vector store |
| `StoreCreated` | After a vector store is created |
| `AddingFileToStore` | Before adding a file to a vector store |
| `FileAddedToStore` | After a file is added to a vector store |
| `RemovingFileFromStore` | Before removing a file from a store |
| `FileRemovedFromStore` | After a file is removed from a store |

Every event follows the same naming convention: the *-ing* form fires before the operation, and the *-ed* form fires after. This pre/post pattern lets you build middleware-like behavior around any AI operation.

#### Building an AI Usage Logger

Let us build a comprehensive event listener that logs every AI interaction for cost tracking and debugging:

```php
<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\AudioGenerated;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Events\ImageGenerated;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Events\TranscriptionGenerated;

class AiUsageLogger
{
    public function handlePromptingAgent(PromptingAgent $event): void
    {
        Log::info('AI prompt started', [
            'agent' => get_class($event->agent),
            'prompt' => Str::limit($event->prompt, 100),
            'provider' => $event->provider?->value,
        ]);
    }

    public function handleAgentPrompted(AgentPrompted $event): void
    {
        Log::info('AI prompt completed', [
            'agent' => get_class($event->agent),
            'input_tokens' => $event->response->usage->inputTokens,
            'output_tokens' => $event->response->usage->outputTokens,
            'provider' => $event->provider?->value,
        ]);

        AiUsageRecord::create([
            'type' => 'agent_prompt',
            'agent' => get_class($event->agent),
            'input_tokens' => $event->response->usage->inputTokens,
            'output_tokens' => $event->response->usage->outputTokens,
            'provider' => $event->provider?->value,
        ]);
    }

    public function handleImageGenerated(ImageGenerated $event): void
    {
        AiUsageRecord::create([
            'type' => 'image_generation',
            'provider' => $event->provider?->value,
        ]);
    }

    public function handleToolInvoked(ToolInvoked $event): void
    {
        Log::info('AI tool invoked', [
            'tool' => get_class($event->tool),
            'agent' => get_class($event->agent),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            PromptingAgent::class => 'handlePromptingAgent',
            AgentPrompted::class => 'handleAgentPrompted',
            AgentStreamed::class => 'handleAgentPrompted',
            ImageGenerated::class => 'handleImageGenerated',
            ToolInvoked::class => 'handleToolInvoked',
        ];
    }
}
```

Register the subscriber in your `EventServiceProvider` or use Laravel's automatic event discovery:

```php
// app/Providers/EventServiceProvider.php
protected $subscribe = [
    AiUsageLogger::class,
];
```

With this listener in place, every AI interaction is logged and recorded. You can build dashboards showing token usage over time, cost breakdowns by agent, tool invocation patterns, and provider reliability metrics. This data is invaluable for optimizing costs — you might discover that one agent accounts for 80% of your token usage, or that a particular tool is being invoked far more often than expected.

#### Monitoring with Metrics

For production applications, emit metrics to your monitoring system of choice:

```php
public function handleAgentPrompted(AgentPrompted $event): void
{
    $totalTokens = $event->response->usage->inputTokens
        + $event->response->usage->outputTokens;

    // Emit to Prometheus, Datadog, New Relic, etc.
    Metrics::counter('ai.tokens.total', $totalTokens, [
        'agent' => class_basename($event->agent),
        'provider' => $event->provider?->value,
    ]);

    Metrics::histogram('ai.response.tokens', $event->response->usage->outputTokens, [
        'agent' => class_basename($event->agent),
    ]);
}
```

---

## Chapter 18: Testing AI Features

Testing AI features is fundamentally different from testing a database query or a form submission. AI responses are non-deterministic — the same prompt can produce different outputs on every call. They are expensive — each test run that hits a live API costs real money. And they are slow — a round trip to an AI provider adds seconds to every test case.

The Laravel AI SDK solves all three problems with a comprehensive faking system. Every AI-capable class in the SDK — agents, images, audio, transcriptions, embeddings, reranking, files, and stores — can be faked. Faked operations return controlled, predictable responses without making any network calls. Combined with a rich set of assertions, you can write thorough test suites that run in milliseconds and cost nothing.

### 18.1 Why AI Testing Is Different

Consider a function that uses an AI agent to moderate user content. In production, the agent sends the content to OpenAI, which analyzes it and returns a moderation decision. If you test this function by calling the real API, you face three problems:

1. **Non-determinism** — The model might flag content as unsafe in one test run and safe in the next. Your assertions become flaky.
2. **Cost** — Each test run consumes tokens. A test suite with 50 moderation tests, run on every CI build, adds up quickly.
3. **Speed** — Each API call takes 1-3 seconds. Multiply that by dozens of test cases, and your feedback loop slows to a crawl.

The solution is the same principle Laravel applies to mail, notifications, and queues: *fake the external dependency and assert against what was dispatched.* You do not test whether OpenAI can analyze content — that is OpenAI's concern. You test whether your application sends the right prompt, handles the response correctly, and behaves appropriately when things go wrong.

### 18.2 Faking Agents, Images, Audio, and More

#### Faking Agents

The simplest fake returns an empty response for any prompt:

```php
use App\Ai\Agents\SalesCoach;

SalesCoach::fake();

$response = (new SalesCoach)->prompt('Analyze this transcript.');

// $response->text contains auto-generated fake content
```

To control the response content, pass an array. Each element is consumed in order:

```php
SalesCoach::fake([
    'Great opening technique! Score: 8/10.',
    'Needs improvement on closing. Score: 5/10.',
]);

$first = (new SalesCoach)->prompt('Analyze transcript 1.');
// $first->text === 'Great opening technique! Score: 8/10.'

$second = (new SalesCoach)->prompt('Analyze transcript 2.');
// $second->text === 'Needs improvement on closing. Score: 5/10.'
```

For dynamic responses based on the prompt content, pass a closure:

```php
use Laravel\Ai\Prompts\AgentPrompt;

SalesCoach::fake(function (AgentPrompt $prompt) {
    if (str_contains($prompt->prompt, 'cold call')) {
        return 'Cold calling analysis: ...';
    }

    return 'General analysis: ...';
});
```

The closure receives an `AgentPrompt` object that gives you access to the full prompt text, allowing you to return different responses based on what was asked.

#### Structured Output Agents Auto-Generate Fake Data

If your agent implements `HasStructuredOutput`, the fake automatically generates data that matches your schema:

```php
use App\Ai\Agents\LeadScorer;

// LeadScorer has schema: { score: integer, reason: string }
LeadScorer::fake();

$response = (new LeadScorer)->prompt('Score this lead.');

// $response['score'] is a valid integer
// $response['reason'] is a valid string
// Both match the schema constraints defined in the agent
```

This is remarkably convenient. You do not need to craft fake JSON responses that match your schema — the SDK generates them for you. For structured output agents, a bare `fake()` call is often all you need.

#### Faking Images

```php
use Laravel\Ai\Image;

Image::fake();

$image = Image::of('A sunset over the ocean')->generate();
// Returns a fake image response without calling any API
```

With controlled responses:

```php
Image::fake([
    base64_encode('fake-image-content-1'),
    base64_encode('fake-image-content-2'),
]);
```

With a closure:

```php
use Laravel\Ai\Prompts\ImagePrompt;

Image::fake(function (ImagePrompt $prompt) {
    return base64_encode("Generated for: {$prompt->prompt}");
});
```

#### Faking Audio

```php
use Laravel\Ai\Audio;

Audio::fake();

$audio = Audio::of('Hello, welcome to our platform.')->generate();
// Returns a fake audio response
```

#### Faking Transcriptions

```php
use Laravel\Ai\Transcription;

Transcription::fake();

$transcript = Transcription::fromStorage('meeting.mp3')->generate();
// Returns fake transcription text

// With controlled responses
Transcription::fake([
    'First transcription result.',
    'Second transcription result.',
]);
```

#### Faking Embeddings

```php
use Laravel\Ai\Embeddings;

Embeddings::fake();

$response = Embeddings::for(['Laravel is great.'])->generate();
// Returns fake embedding vectors
```

#### Faking Reranking

```php
use Laravel\Ai\Reranking;
use Laravel\Ai\Responses\RankedDocument;

Reranking::fake();

// Or with specific ranked results
Reranking::fake([
    [
        new RankedDocument(index: 1, document: 'Laravel is a PHP framework.', score: 0.95),
        new RankedDocument(index: 0, document: 'Django is a Python framework.', score: 0.32),
    ],
]);
```

### 18.3 Assertions and Expectations

Faking is only half the equation. After faking, you need to *assert* that your code interacted with the AI system correctly. The SDK provides a complete set of assertions for every fakeable class.

#### Agent Assertions

```php
use App\Ai\Agents\SalesCoach;
use Laravel\Ai\Prompts\AgentPrompt;

SalesCoach::fake();

// ... run the code under test ...

// Assert a specific prompt was sent
SalesCoach::assertPrompted('Analyze this sales transcript.');

// Assert using a closure for flexible matching
SalesCoach::assertPrompted(function (AgentPrompt $prompt) {
    return str_contains($prompt->prompt, 'sales transcript')
        && $prompt->provider === Lab::OpenAI;
});

// Assert a prompt was NOT sent
SalesCoach::assertNotPrompted('Some other prompt');

// Assert the agent was never prompted at all
SalesCoach::assertNeverPrompted();
```

#### Image Assertions

```php
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt;

Image::fake();

// ... generate images ...

Image::assertGenerated(function (ImagePrompt $prompt) {
    return str_contains($prompt->prompt, 'sunset')
        && $prompt->isLandscape();
});

Image::assertNotGenerated('A prompt that was never used');

Image::assertNothingGenerated();
```

#### Audio Assertions

```php
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AudioPrompt;

Audio::fake();

// ... generate audio ...

Audio::assertGenerated(function (AudioPrompt $prompt) {
    return str_contains($prompt->prompt, 'Hello')
        && $prompt->isFemale();
});

Audio::assertNothingGenerated();
```

#### Transcription Assertions

```php
use Laravel\Ai\Transcription;
use Laravel\Ai\Prompts\TranscriptionPrompt;

Transcription::fake();

// ... transcribe audio ...

Transcription::assertGenerated(function (TranscriptionPrompt $prompt) {
    return $prompt->language === 'en'
        && $prompt->isDiarized();
});
```

#### Embeddings Assertions

```php
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

Embeddings::fake();

// ... generate embeddings ...

Embeddings::assertGenerated(function (EmbeddingsPrompt $prompt) {
    return str_contains($prompt->input[0], 'Laravel')
        && $prompt->dimensions === 1536;
});
```

### 18.4 Preventing Stray API Calls

One of the most dangerous situations in testing is an accidental live API call. You fake one agent but forget to fake another, and suddenly your test suite is making real (and expensive) requests to OpenAI.

The `preventStrayPrompts()` method throws an exception if any prompt is sent that was not explicitly faked:

```php
SalesCoach::fake()->preventStrayPrompts();

// This works fine — SalesCoach is faked
(new SalesCoach)->prompt('Analyze this.');

// This would throw an exception — ContentWriter is NOT faked
(new ContentWriter)->prompt('Write something.');
```

Every fakeable class has its own "prevent stray" method:

```php
Image::fake()->preventStrayImages();
Audio::fake()->preventStrayAudio();
Transcription::fake()->preventStrayTranscriptions();
Embeddings::fake()->preventStrayEmbeddings();
```

For maximum safety in your test base class, call these in your `setUp()` method:

```php
<?php

namespace Tests;

use App\Ai\Agents\SalesCoach;
use App\Ai\Agents\ContentWriter;
use Laravel\Ai\Audio;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Image;
use Laravel\Ai\Transcription;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Image::fake()->preventStrayImages();
        Audio::fake()->preventStrayAudio();
        Transcription::fake()->preventStrayTranscriptions();
        Embeddings::fake()->preventStrayEmbeddings();
    }
}
```

With this in place, any test that accidentally triggers a live AI call will fail immediately with a clear error message rather than silently burning through your API budget.

### 18.5 Testing Queued Operations

When you use `queue()` on an agent, the prompt is dispatched to Laravel's queue system rather than executed immediately. The SDK provides dedicated assertions for verifying queued behavior:

```php
use App\Ai\Agents\ProductWriter;
use Laravel\Ai\Prompts\AgentPrompt;

ProductWriter::fake();

// ... trigger the code that queues a prompt ...

ProductWriter::assertQueued('Write a description for Widget Pro.');

ProductWriter::assertQueued(function (AgentPrompt $prompt) {
    return str_contains($prompt->prompt, 'Widget Pro');
});

ProductWriter::assertNotQueued('Some other prompt');

ProductWriter::assertNeverQueued();
```

Queued image assertions follow the same pattern:

```php
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\QueuedImagePrompt;

Image::fake();

// ... trigger queued image generation ...

Image::assertQueued(function (QueuedImagePrompt $prompt) {
    return str_contains($prompt->prompt, 'product photo')
        && $prompt->isSquare();
});
```

### 18.6 Testing Files and Stores

The `Files` and `Stores` facades also support faking and assertions for testing file storage and vector store operations.

#### Faking Files

```php
use Laravel\Ai\Files;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\StorableFile;

Files::fake();

// ... code that stores and deletes files ...

Files::assertStored(function (StorableFile $file) {
    return (string) $file === 'Hello, Laravel!';
});

Files::assertDeleted('file-abc123');

Files::assertNothingStored();
```

#### Faking Vector Stores

```php
use Laravel\Ai\Stores;
use Laravel\Ai\Files\StorableFile;

Stores::fake();

$store = Stores::create('Knowledge Base');

Stores::assertCreated('Knowledge Base');

$store->add(Document::fromString('Some content', 'text/plain'));
$store->assertAdded(function (StorableFile $file) {
    return $file->name() === 'hello.txt';
});

$store->remove('file-id');
$store->assertRemoved('file-id');

Stores::assertDeleted('store_id');
```

### 18.7 A Complete Test Suite Example

Let us tie everything together with a realistic test suite for a feature that uses multiple AI capabilities: a product catalog system that generates descriptions, creates images, and stores embeddings for semantic search.

```php
<?php

namespace Tests\Feature;

use App\Ai\Agents\ProductWriter;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Laravel\Ai\Prompts\ImagePrompt;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ProductWriter::fake([
            'A revolutionary widget that simplifies your workflow. '
            . 'Built with premium materials and designed for professionals.',
        ]);

        Image::fake();
        Embeddings::fake();
    }

    public function test_generating_product_description(): void
    {
        $product = Product::factory()->create(['name' => 'Widget Pro']);

        $this->postJson("/api/products/{$product->id}/generate-description")
            ->assertOk();

        ProductWriter::assertPrompted(function (AgentPrompt $prompt) {
            return str_contains($prompt->prompt, 'Widget Pro');
        });

        $product->refresh();
        $this->assertNotNull($product->ai_description);
        $this->assertStringContains('revolutionary widget', $product->ai_description);
    }

    public function test_generating_product_image(): void
    {
        $product = Product::factory()->create([
            'name' => 'Widget Pro',
            'category' => 'Electronics',
        ]);

        $this->postJson("/api/products/{$product->id}/generate-image")
            ->assertOk();

        Image::assertGenerated(function (ImagePrompt $prompt) {
            return str_contains($prompt->prompt, 'Widget Pro')
                && str_contains($prompt->prompt, 'Electronics')
                && $prompt->isSquare();
        });
    }

    public function test_product_embeddings_are_generated_on_description_update(): void
    {
        $product = Product::factory()->create([
            'name' => 'Widget Pro',
            'ai_description' => 'A great product for professionals.',
        ]);

        $this->putJson("/api/products/{$product->id}", [
            'ai_description' => 'An updated description for the product.',
        ])->assertOk();

        Embeddings::assertGenerated(function (EmbeddingsPrompt $prompt) {
            return str_contains($prompt->input[0], 'updated description');
        });
    }

    public function test_description_generation_is_queued_for_bulk_operations(): void
    {
        $products = Product::factory()->count(5)->create();

        $this->postJson('/api/products/bulk-generate-descriptions', [
            'product_ids' => $products->pluck('id')->toArray(),
        ])->assertAccepted();

        ProductWriter::assertQueued(function (AgentPrompt $prompt) use ($products) {
            return str_contains($prompt->prompt, $products->first()->name);
        });
    }

    public function test_handles_ai_failure_gracefully(): void
    {
        ProductWriter::fake(function () {
            throw new \RuntimeException('Provider unavailable');
        });

        $product = Product::factory()->create(['name' => 'Widget Pro']);

        $this->postJson("/api/products/{$product->id}/generate-description")
            ->assertStatus(500)
            ->assertJson(['error' => 'Description generation failed. Please try again.']);

        $product->refresh();
        $this->assertNull($product->ai_description);
    }

    public function test_no_stray_ai_calls(): void
    {
        ProductWriter::fake()->preventStrayPrompts();
        Image::fake()->preventStrayImages();
        Embeddings::fake()->preventStrayEmbeddings();

        $product = Product::factory()->create(['name' => 'Widget Pro']);

        // Only the description endpoint — should not trigger images or embeddings
        $this->postJson("/api/products/{$product->id}/generate-description")
            ->assertOk();

        ProductWriter::assertPrompted();
        Image::assertNothingGenerated();
    }
}
```

This test suite demonstrates six key testing patterns:

1. **Controlled responses** — Fake the agent with a specific response and verify it is stored correctly.
2. **Prompt assertions** — Verify that the correct prompt was sent with the right parameters.
3. **Cross-feature testing** — Test that updating a description triggers embedding generation.
4. **Queue assertions** — Verify that bulk operations dispatch prompts to the queue.
5. **Error handling** — Fake an exception to test graceful degradation.
6. **Stray call prevention** — Ensure an endpoint only triggers the AI operations it should.

Each test runs in milliseconds, costs nothing, and produces deterministic results. You can run this suite hundreds of times a day on CI without worrying about API costs or flaky test failures caused by model non-determinism.

---

### Chapter Summary

Part V has equipped you with the production patterns that transform AI experiments into reliable, scalable features:

- **Streaming** delivers responses token by token, dramatically improving perceived performance. Use `stream()` for SSE, `usingVercelDataProtocol()` for modern frontend frameworks, and manual iteration for custom streaming logic.
- **Broadcasting and queuing** decouple AI workloads from HTTP requests. Broadcast streamed events over WebSockets for real-time updates, and queue prompts for background processing with proper error handling via `then()` and `catch()`.
- **Failover** protects against provider outages by trying multiple providers in sequence. Combined with rate limiting and graceful degradation, your AI features remain available even when individual providers go down.
- **Testing** makes AI code deterministic and free. Fake every AI class, assert against prompts and operations, prevent stray API calls, and build comprehensive test suites that run in seconds.

In Part VI, you will extend these patterns further by building MCP servers that expose your Laravel application's capabilities to external AI systems — completing the loop from consuming AI services to providing them.
