# Appendices

---

# Appendix A: Provider Reference

The Laravel AI SDK supports a growing list of AI providers through the `Laravel\AI\Enums\Lab` enum. Each provider supports a different combination of features. This appendix provides a complete reference for choosing and configuring providers.

## A.1 Provider Feature Matrix

| Provider | Lab Enum | Text | Images | TTS | STT | Embeddings | Reranking | Files | Custom URL |
|---|---|---|---|---|---|---|---|---|---|
| OpenAI | `Lab::OpenAI` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Anthropic | `Lab::Anthropic` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Gemini | `Lab::Gemini` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Azure | `Lab::Azure` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Groq | `Lab::Groq` | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| xAI | `Lab::xAI` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| DeepSeek | `Lab::DeepSeek` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Mistral | `Lab::Mistral` | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ollama | `Lab::Ollama` | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| Cohere | `Lab::Cohere` | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| ElevenLabs | `Lab::ElevenLabs` | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Jina | `Lab::Jina` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| VoyageAI | `Lab::VoyageAI` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |

> **Note**: Feature availability reflects the SDK's v0.2.x release. Providers may add features in future SDK versions.

## A.2 Recommended Models per Provider

### Text Generation

| Provider | Cheapest Model | Smartest Model |
|---|---|---|
| OpenAI | `gpt-4o-mini` | `gpt-4o` |
| Anthropic | `claude-3-5-haiku-latest` | `claude-sonnet-4-20250514` |
| Gemini | `gemini-2.0-flash-lite` | `gemini-2.5-pro-preview-06-05` |
| Azure | `gpt-4o-mini` (deployed) | `gpt-4o` (deployed) |
| Groq | `llama-3.3-70b-versatile` | `llama-3.3-70b-versatile` |
| xAI | `grok-3-mini-fast` | `grok-3` |
| DeepSeek | `deepseek-chat` | `deepseek-reasoner` |
| Mistral | `mistral-small-latest` | `mistral-large-latest` |
| Ollama | `llama3.2:3b` | `llama3.3:70b` |
| Cohere | `command-r` | `command-r-plus` |

### Image Generation

| Provider | Model | Notes |
|---|---|---|
| OpenAI | `gpt-image-1` | Best overall quality and prompt adherence |
| Gemini | `gemini-2.0-flash` | Integrated with text model, good for inline generation |
| xAI | `grok-2-image` | Fast generation, strong with creative prompts |
| Mistral | `mistral-small-latest` | Emerging capability, check latest docs |

### Text-to-Speech

| Provider | Cheapest | Best Quality |
|---|---|---|
| OpenAI | `tts-1` | `tts-1-hd` |
| Gemini | `gemini-2.5-flash-preview-tts` | `gemini-2.5-flash-preview-tts` |
| ElevenLabs | `eleven_flash_v2_5` | `eleven_multilingual_v2` |

### Speech-to-Text

| Provider | Model | Notes |
|---|---|---|
| OpenAI | `gpt-4o-transcribe` | Excellent accuracy, supports diarization |
| Gemini | `gemini-2.0-flash` | Good multilingual support |
| Groq | `whisper-large-v3-turbo` | Very fast transcription |
| ElevenLabs | `scribe_v1` | Supports diarization, 99+ languages |
| Mistral | `mistral-small-latest` | Emerging capability |

### Embeddings

| Provider | Cheapest | Best Quality | Max Dimensions |
|---|---|---|---|
| OpenAI | `text-embedding-3-small` | `text-embedding-3-large` | 3,072 |
| Gemini | `text-embedding-004` | `text-embedding-004` | 768 |
| Mistral | `mistral-embed` | `mistral-embed` | 1,024 |
| Cohere | `embed-english-light-v3.0` | `embed-multilingual-v3.0` | 1,024 |
| Jina | `jina-embeddings-v3` | `jina-embeddings-v3` | 1,024 |
| VoyageAI | `voyage-3-lite` | `voyage-3-large` | 2,048 |
| Ollama | `nomic-embed-text` | `mxbai-embed-large` | 1,024 |

### Reranking

| Provider | Model | Notes |
|---|---|---|
| Cohere | `rerank-v3.5` | Industry standard, excellent accuracy |
| Jina | `jina-reranker-v2-base-multilingual` | Good multilingual support |
| VoyageAI | `rerank-2` | Strong performance on technical content |

## A.3 Provider Configuration

Every provider requires an API key set in your `.env` file. The naming convention follows the pattern `{PROVIDER}_API_KEY`:

```ini
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GEMINI_API_KEY=AIza...
GROQ_API_KEY=gsk_...
XAI_API_KEY=xai-...
DEEPSEEK_API_KEY=sk-...
MISTRAL_API_KEY=...
COHERE_API_KEY=...
ELEVENLABS_API_KEY=...
JINA_API_KEY=...
VOYAGEAI_API_KEY=...
```

For providers with custom URL support, you can override the base URL:

```ini
OPENAI_URL=https://your-proxy.example.com/v1
OLLAMA_URL=http://localhost:11434
```

Azure requires additional configuration:

```ini
AZURE_API_KEY=...
AZURE_URL=https://your-resource.openai.azure.com
AZURE_API_VERSION=2024-12-01-preview
```

---

# Appendix B: Complete API Reference

This appendix catalogs every Artisan command, interface, trait, method, attribute, and fluent API surface in the Laravel AI SDK.

## B.1 Artisan Commands

| Command | Description |
|---|---|
| `php artisan make:agent {Name}` | Create a new agent class in `app/Agents/` |
| `php artisan make:agent {Name} --structured` | Create an agent with `HasStructuredOutput` interface |
| `php artisan make:tool {Name}` | Create a new tool class in `app/Tools/` |
| `php artisan make:agent-middleware {Name}` | Create agent middleware in `app/AgentMiddleware/` |
| `php artisan make:mcp-server {Name}` | Create an MCP server class in `app/Mcp/Servers/` |
| `php artisan make:mcp-tool {Name}` | Create an MCP tool class in `app/Mcp/Tools/` |
| `php artisan make:mcp-resource {Name}` | Create an MCP resource class in `app/Mcp/Resources/` |
| `php artisan make:mcp-prompt {Name}` | Create an MCP prompt class in `app/Mcp/Prompts/` |

## B.2 Agent Interfaces

### `Laravel\AI\Contracts\Agent`

The base interface for all agents. Requires:

```php
public function instructions(): string;
```

### `Laravel\AI\Contracts\Conversational`

Enables multi-turn conversation memory. Requires:

```php
public function id(): string|int;
```

### `Laravel\AI\Contracts\HasTools`

Declares that the agent can use tools. Requires:

```php
/** @return array<int, class-string> */
public function tools(): array;
```

### `Laravel\AI\Contracts\HasStructuredOutput`

Declares that the agent returns structured JSON output. Requires:

```php
public function schema(): array;
```

### `Laravel\AI\Contracts\HasMiddleware`

Declares that the agent uses middleware. Requires:

```php
/** @return array<int, class-string|object> */
public static function middleware(): array;
```

## B.3 Agent Traits

### `Promptable`

Automatically included on all agents. Provides:

- `prompt(string $message): AgentResponse` — Send a prompt and receive a response.
- `stream(string $message): StreamedAgentResponse` — Stream a response via SSE.
- `queue(string $message): PendingAgentTask` — Queue the prompt for background processing.
- `broadcastOnQueue(string $message): PendingAgentTask` — Queue and broadcast streamed events.
- `forUser(Authenticatable $user): static` — Scope the agent to a specific user.
- `continue(string $conversationId): static` — Resume a previous conversation.
- `make(): static` — Create an agent instance via the container.

### `RemembersConversations`

Adds automatic conversation persistence. When applied alongside `Conversational`, the agent automatically stores and retrieves conversation history from the database without manual intervention.

## B.4 Configuration Attributes

These PHP 8.4 attributes are applied to agent classes to configure behavior:

| Attribute | Example | Description |
|---|---|---|
| `#[Provider(Lab::Anthropic)]` | Set the AI provider | Overrides the default provider |
| `#[Model('claude-sonnet-4-20250514')]` | Set the model name | Provider-specific model identifier |
| `#[MaxSteps(10)]` | Maximum tool-use loops | Prevents infinite tool-call chains |
| `#[MaxTokens(4096)]` | Response token limit | Caps the length of generated output |
| `#[Temperature(0.7)]` | Creativity control | 0.0 = deterministic, 1.0+ = creative |
| `#[Timeout(120)]` | Request timeout in seconds | How long to wait before timing out |
| `#[UseCheapestModel]` | Use the cheapest model | Provider selects its cheapest option |
| `#[UseSmartestModel]` | Use the smartest model | Provider selects its most capable option |

Usage example:

```php
use Laravel\AI\Attributes\{Provider, Model, MaxSteps, Temperature};
use Laravel\AI\Enums\Lab;

#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-20250514')]
#[MaxSteps(5)]
#[Temperature(0.3)]
class AnalysisAgent implements Agent
{
    // ...
}
```

## B.5 Image API

```php
use Laravel\AI\Facades\Image;

Image::of('A sunset over a mountain lake')  // Set the prompt
    ->on(Lab::OpenAI)                        // Set the provider
    ->using('gpt-image-1')                   // Set the model
    ->generate();                            // Returns ImageResponse

// Aspect ratio helpers
Image::of('...')->square()->generate();      // 1:1
Image::of('...')->portrait()->generate();    // 3:4 or similar
Image::of('...')->landscape()->generate();   // 4:3 or similar

// Quality and timeout
Image::of('...')->quality('hd')->timeout(120)->generate();

// Attachments (image remixing)
Image::of('Make this logo blue')
    ->attachments([Document::fromPath('/path/to/logo.png')])
    ->generate();

// Storage
Image::of('...')->store('images');                        // Default disk
Image::of('...')->storeAs('images', 'hero.png');          // Custom filename
Image::of('...')->storePublicly('images');                // Public disk
Image::of('...')->storePubliclyAs('images', 'hero.png'); // Public + name

// Queued generation
Image::of('...')
    ->store('images')
    ->queue()
    ->then(fn ($path) => logger()->info("Stored: {$path}"))
    ->catch(fn ($e) => logger()->error($e->getMessage()));
```

## B.6 Audio API

```php
use Laravel\AI\Facades\Audio;

Audio::of('Welcome to our application.')    // Set the text
    ->on(Lab::OpenAI)                       // Set the provider
    ->using('tts-1-hd')                     // Set the model
    ->generate();                           // Returns AudioResponse

// Voice selection
Audio::of('...')->male()->generate();       // Male voice
Audio::of('...')->female()->generate();     // Female voice
Audio::of('...')->voice('shimmer')->generate(); // Specific named voice

// Instructions for delivery style
Audio::of('Breaking news: ...')
    ->instructions('Speak with urgency and energy.')
    ->generate();

// Storage
Audio::of('...')->store('audio');
Audio::of('...')->storeAs('audio', 'welcome.mp3');
Audio::of('...')->storePublicly('audio');
Audio::of('...')->storePubliclyAs('audio', 'welcome.mp3');

// Queued generation
Audio::of('...')->store('audio')->queue();
```

## B.7 Transcription API

```php
use Laravel\AI\Facades\Transcription;

// Source methods
Transcription::fromPath('/absolute/path/to/audio.mp3');
Transcription::fromStorage('audio/recording.mp3');
Transcription::fromUpload($request->file('audio'));

// Generate
$text = Transcription::fromPath('...')->generate();

// Provider and model
Transcription::fromPath('...')
    ->on(Lab::OpenAI)
    ->using('gpt-4o-transcribe')
    ->generate();

// Speaker diarization
$transcript = Transcription::fromPath('...')
    ->diarize()
    ->generate();

// Queued transcription
Transcription::fromPath('...')
    ->queue()
    ->then(fn ($text) => /* process */)
    ->catch(fn ($e) => /* handle error */);
```

## B.8 Embeddings API

```php
use Illuminate\Support\Str;
use Laravel\AI\Facades\Embeddings;

// Quick string helper
$vector = Str::toEmbeddings('The quick brown fox');

// Full fluent API
$vectors = Embeddings::for('The quick brown fox')
    ->on(Lab::OpenAI)
    ->using('text-embedding-3-small')
    ->dimensions(1536)
    ->cache(seconds: 3600)
    ->generate();

// Batch embeddings
$vectors = Embeddings::for([
    'First document',
    'Second document',
    'Third document',
])->generate();
```

## B.9 Reranking API

```php
use Laravel\AI\Facades\Reranking;

// Rerank an array of documents
$ranked = Reranking::of([
    'Laravel is a PHP framework',
    'React is a JavaScript library',
    'Vue.js works great with Laravel',
])
->on(Lab::Cohere)
->using('rerank-v3.5')
->limit(2)
->rerank('best framework for PHP');

// Rerank a Laravel collection (macro)
$articles = Article::all();
$ranked = $articles->rerank(
    query: 'machine learning tutorials',
    key: 'content',
    limit: 5
);
```

## B.10 Files API

```php
use Laravel\AI\Facades\Files;
use Laravel\AI\Attachments\Document;
use Laravel\AI\Attachments\Image as ImageAttachment;

// Creating file references
$doc = Document::fromPath('/path/to/file.pdf');
$doc = Document::fromStorage('documents/file.pdf');
$doc = Document::fromUrl('https://example.com/file.pdf');
$doc = Document::fromString('raw content', 'file.txt');
$doc = Document::fromUpload($request->file('document'));
$doc = Document::fromId('file-abc123', Lab::OpenAI);

$img = ImageAttachment::fromPath('/path/to/image.png');
$img = ImageAttachment::fromUrl('https://example.com/photo.jpg');

// File operations with providers
Files::on(Lab::OpenAI)->put($document);   // Upload to provider
Files::on(Lab::OpenAI)->get('file-id');   // Retrieve file metadata
Files::on(Lab::OpenAI)->delete('file-id');// Delete from provider
```

## B.11 Stores API (Vector Stores)

```php
use Laravel\AI\Facades\Stores;

// Create a vector store
$store = Stores::on(Lab::OpenAI)->create('knowledge-base');

// Get an existing store
$store = Stores::on(Lab::OpenAI)->get('vs_abc123');

// Delete a store
Stores::on(Lab::OpenAI)->delete('vs_abc123');

// Add files to a store
Stores::on(Lab::OpenAI)->add('vs_abc123', [
    Document::fromPath('/path/to/doc1.pdf'),
    Document::fromStorage('docs/doc2.pdf'),
]);

// Remove files from a store
Stores::on(Lab::OpenAI)->remove('vs_abc123', ['file-id-1', 'file-id-2']);
```

## B.12 Vector Query Methods (Eloquent)

These methods are available on Eloquent models with vector columns when using PostgreSQL with pgvector:

```php
use App\Models\Article;

Article::query()
    ->whereVectorSimilarTo('embedding', $queryVector)
    ->whereVectorDistanceLessThan('embedding', $queryVector, 0.5)
    ->selectVectorDistance('embedding', $queryVector)
    ->orderByVectorDistance('embedding', $queryVector)
    ->limit(10)
    ->get();
```

| Method | Description |
|---|---|
| `whereVectorSimilarTo($column, $vector)` | Filter to rows with similar vectors |
| `whereVectorDistanceLessThan($column, $vector, $distance)` | Filter by maximum distance threshold |
| `selectVectorDistance($column, $vector)` | Add computed distance to the SELECT clause |
| `orderByVectorDistance($column, $vector)` | Sort results by proximity (nearest first) |

---

# Appendix C: Event Reference

The Laravel AI SDK dispatches events at key points throughout every operation. You can listen to these events for logging, monitoring, rate-limit tracking, billing, or custom business logic. Register listeners in your `EventServiceProvider` or use closures in `AppServiceProvider::boot()`.

All events live in the `Laravel\AI\Events` namespace.

## C.1 Agent Events

| Event | Timing | Key Properties |
|---|---|---|
| `PromptingAgent` | Before agent prompt | `$agent`, `$message`, `$user` |
| `AgentPrompted` | After agent prompt completes | `$agent`, `$message`, `$response`, `$user` |
| `StreamingAgent` | Before agent stream begins | `$agent`, `$message`, `$user` |
| `AgentStreamed` | After agent stream completes | `$agent`, `$message`, `$response`, `$user` |

## C.2 Tool Events

| Event | Timing | Key Properties |
|---|---|---|
| `InvokingTool` | Before a tool is invoked | `$tool`, `$arguments`, `$agent` |
| `ToolInvoked` | After a tool returns | `$tool`, `$arguments`, `$result`, `$agent` |

## C.3 Image Events

| Event | Timing | Key Properties |
|---|---|---|
| `GeneratingImage` | Before image generation | `$prompt`, `$provider`, `$model` |
| `ImageGenerated` | After image generation | `$prompt`, `$provider`, `$model`, `$response` |

## C.4 Audio Events

| Event | Timing | Key Properties |
|---|---|---|
| `GeneratingAudio` | Before TTS generation | `$text`, `$provider`, `$model` |
| `AudioGenerated` | After TTS generation | `$text`, `$provider`, `$model`, `$response` |
| `GeneratingTranscription` | Before STT transcription | `$source`, `$provider`, `$model` |
| `TranscriptionGenerated` | After STT transcription | `$source`, `$provider`, `$model`, `$response` |

## C.5 Embeddings Events

| Event | Timing | Key Properties |
|---|---|---|
| `GeneratingEmbeddings` | Before embedding generation | `$input`, `$provider`, `$model` |
| `EmbeddingsGenerated` | After embedding generation | `$input`, `$provider`, `$model`, `$response` |

## C.6 Reranking Events

| Event | Timing | Key Properties |
|---|---|---|
| `Reranking` | Before reranking | `$query`, `$documents`, `$provider` |
| `Reranked` | After reranking | `$query`, `$documents`, `$provider`, `$results` |

## C.7 File and Store Events

| Event | Timing | Key Properties |
|---|---|---|
| `StoringFile` | Before file upload | `$file`, `$provider` |
| `FileStored` | After file upload | `$file`, `$provider`, `$fileId` |
| `FileDeleted` | After file deletion | `$fileId`, `$provider` |
| `CreatingStore` | Before vector store creation | `$name`, `$provider` |
| `StoreCreated` | After vector store creation | `$name`, `$provider`, `$storeId` |
| `AddingFileToStore` | Before adding file to store | `$storeId`, `$file`, `$provider` |
| `FileAddedToStore` | After adding file to store | `$storeId`, `$file`, `$provider` |
| `RemovingFileFromStore` | Before removing file from store | `$storeId`, `$fileId`, `$provider` |
| `FileRemovedFromStore` | After removing file from store | `$storeId`, `$fileId`, `$provider` |

## C.8 Listening to Events

```php
// In EventServiceProvider
protected $listen = [
    \Laravel\AI\Events\AgentPrompted::class => [
        \App\Listeners\LogAgentUsage::class,
    ],
];

// Or in AppServiceProvider::boot()
use Illuminate\Support\Facades\Event;
use Laravel\AI\Events\AgentPrompted;

Event::listen(AgentPrompted::class, function (AgentPrompted $event) {
    logger()->info('Agent prompted', [
        'agent' => get_class($event->agent),
        'tokens' => $event->response->usage(),
    ]);
});
```

---

# Appendix D: Troubleshooting Guide

This appendix covers the most common issues encountered when working with the Laravel AI SDK and provides tested solutions.

## D.1 Missing or Invalid API Keys

**Symptom**: `RuntimeException: No API key configured for provider [openai].`

**Solution**: Ensure the correct environment variable is set in your `.env` file. Each provider follows the convention `{PROVIDER}_API_KEY`. After adding or changing a key, clear the config cache:

```bash
php artisan config:clear
```

Verify the key is loaded:

```bash
php artisan tinker
>>> config('ai.providers.openai.key')
```

If using Laravel Forge or Vapor, set the environment variable in the hosting platform's environment settings and redeploy.

## D.2 Provider Not Supported for Feature

**Symptom**: `InvalidArgumentException: Provider [deepseek] does not support [image_generation].`

**Solution**: Not all providers support all features. Consult the Provider Feature Matrix in Appendix A. Choose a provider that supports the feature you need:

```php
// DeepSeek doesn't support images — use OpenAI instead
Image::of('A sunrise')->on(Lab::OpenAI)->generate();
```

## D.3 Timeout Errors

**Symptom**: `ConnectionException: cURL error 28: Operation timed out.`

**Causes and solutions**:

1. **Long-running prompts**: Increase the timeout on your agent with the `#[Timeout(120)]` attribute or pass it fluently.
2. **Image generation**: Image models can take 30–90 seconds. Use `->timeout(120)` on the Image facade.
3. **Large transcriptions**: Audio files over 10 minutes may need extended timeouts. Consider splitting large files.
4. **PHP configuration**: Ensure `max_execution_time` in `php.ini` exceeds your longest expected AI operation.
5. **Queue workers**: When using queues, set the `--timeout` flag on the worker above your expected operation time: `php artisan queue:work --timeout=300`.

## D.4 Rate Limit Errors

**Symptom**: `429 Too Many Requests` or `RateLimitException`.

**Solutions**:

1. **Implement backoff**: Use Laravel's retry helper or the SDK's built-in failover.
2. **Queue with throttling**: Use a rate-limited queue to spread requests over time:

```php
// In your queue configuration (config/queue.php)
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'ai',
    'retry_after' => 300,
],
```

3. **Multi-provider failover**: Configure multiple providers so the agent falls back when one is rate-limited.
4. **Track usage with events**: Listen to `AgentPrompted` and `ImageGenerated` events to monitor your consumption rate and alert before you hit limits.

## D.5 pgvector Installation Issues

**Symptom**: `ERROR: could not open extension control file "pgvector"` or `type "vector" does not exist`.

**Step-by-step fix**:

1. **Install the pgvector extension** (Ubuntu/Debian):

```bash
sudo apt install postgresql-17-pgvector
```

On macOS with Homebrew:

```bash
brew install pgvector
```

2. **Enable in PostgreSQL**:

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

3. **For Docker environments**, use an image with pgvector pre-installed:

```yaml
# docker-compose.yml
services:
  postgres:
    image: pgvector/pgvector:pg17
    environment:
      POSTGRES_DB: laravel
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: secret
```

4. **Verify installation**:

```sql
SELECT * FROM pg_extension WHERE extname = 'vector';
```

## D.6 Migration Failures

**Symptom**: `SQLSTATE[42704]: Undefined object: 7 ERROR: type "vector" does not exist`.

**Solution**: The pgvector extension must be created before any migration that uses vector columns. Create a migration that runs first (use an early timestamp):

```php
// 0001_01_01_000000_create_pgvector_extension.php
return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector');
    }
};
```

Ensure this migration file has a timestamp earlier than any migration referencing vector columns.

## D.7 Queue Configuration for AI Jobs

**Symptom**: Queued AI operations never execute, silently fail, or time out.

**Checklist**:

1. **Run the worker** with sufficient timeout:

```bash
php artisan queue:work --queue=ai --timeout=300 --memory=512
```

2. **Set the queue connection** in `.env`:

```ini
QUEUE_CONNECTION=redis
```

3. **Configure retry and backoff** in your queue configuration to handle transient API failures.

4. **Monitor failed jobs**:

```bash
php artisan queue:failed
php artisan queue:retry all
```

5. **Use Horizon** for Redis-based queues to monitor AI job throughput and failure rates in a dashboard.

## D.8 Memory Limits with Large Embeddings

**Symptom**: `Allowed memory size of X bytes exhausted` when generating or processing embeddings.

**Solutions**:

1. **Increase PHP memory limit**:

```ini
; php.ini
memory_limit = 512M
```

2. **Process in batches**: Instead of embedding thousands of documents at once, chunk them:

```php
$documents->chunk(100)->each(function ($batch) {
    Embeddings::for($batch->pluck('content')->toArray())->generate();
});
```

3. **Reduce dimensions**: Use the `->dimensions()` method to request fewer dimensions when full fidelity isn't needed:

```php
Embeddings::for($text)->dimensions(256)->generate();
```

4. **Queue workers**: Set `--memory=512` on queue workers processing embedding jobs.

## D.9 Streaming Not Working

**Symptom**: Streamed responses arrive all at once instead of incrementally, or the connection drops.

**Causes and solutions**:

1. **PHP output buffering**: Disable output buffering for streaming routes:

```php
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

while (ob_get_level()) {
    ob_end_flush();
}
```

2. **Nginx buffering**: Add to your Nginx location block:

```nginx
location /api/chat {
    proxy_buffering off;
    proxy_cache off;
    proxy_set_header X-Accel-Buffering no;
    proxy_read_timeout 300s;
}
```

3. **Apache**: Enable `mod_headers` and set:

```apache
SetEnv no-gzip 1
Header set X-Accel-Buffering "no"
```

4. **Laravel Octane**: If using Octane with Swoole, ensure you're using the `->toResponse()` method on stream responses, which handles Swoole's buffering correctly.

5. **Cloudflare / CDN**: Disable response buffering for your streaming endpoints, or use WebSocket-based broadcasting instead of SSE.

## D.10 Common Debugging Techniques

When all else fails, these techniques help isolate issues:

```php
// 1. Test provider connectivity in Tinker
php artisan tinker
>>> (new \App\Agents\MyAgent)->prompt('Hello');

// 2. Enable HTTP client logging
// In AppServiceProvider::boot():
Http::globalMiddleware(
    Middleware::log(logger(), new MessageFormatter(MessageFormatter::DEBUG))
);

// 3. Listen to all AI events
Event::listen('Laravel\AI\Events\*', function ($eventName, $data) {
    logger()->debug($eventName, ['data' => $data]);
});

// 4. Check provider status pages
// OpenAI: status.openai.com
// Anthropic: status.anthropic.com
// Google AI: status.cloud.google.com
```

---

# Appendix E: Resources and Further Reading

## E.1 Official Laravel Resources

| Resource | URL |
|---|---|
| Laravel AI SDK Documentation | [laravel.com/docs/12.x/ai-sdk](https://laravel.com/docs/12.x/ai-sdk) |
| Laravel MCP Documentation | [laravel.com/docs/12.x/mcp](https://laravel.com/docs/12.x/mcp) |
| Laravel AI SDK Source Code | [github.com/laravel/ai](https://github.com/laravel/ai) |
| Laravel MCP Source Code | [github.com/laravel/mcp](https://github.com/laravel/mcp) |
| Laravel News | [laravel-news.com](https://laravel-news.com) |
| Laracasts | [laracasts.com](https://laracasts.com) |

## E.2 Community Tutorials and Guides

| Author | Title / Resource | Link |
|---|---|---|
| Tobias Schäfer | Practical guide to the Laravel AI SDK — hands-on walkthrough of agents, tools, and structured output with real-world examples | [schaefer-tobias.de](https://schaefer-tobias.de) |
| Mohammad Ali | Showcase tutorial demonstrating multi-provider agent setups and streaming interfaces | Community blog |
| Coder Manjeet | Medium articles covering Laravel AI SDK patterns, embeddings integration, and production deployment tips | [medium.com/@codermanjeet](https://medium.com/@codermanjeet) |
| dev.to Laravel Tag | Community-contributed articles on Laravel AI development | [dev.to/t/laravel](https://dev.to/t/laravel) |
| Laravel Daily | Video tutorials and blog posts on practical Laravel AI features | [laraveldaily.com](https://laraveldaily.com) |

## E.3 Related PHP Libraries

| Library | Description | Link |
|---|---|---|
| Prism PHP | Open-source multi-provider AI abstraction layer for PHP; predated the official SDK and now serves as its underlying provider layer. Created by the Prism PHP community, adopted by Taylor Otwell as the foundation for the official Laravel AI SDK | [github.com/prism-php/prism](https://github.com/prism-php/prism) |
| pgvector-php | PHP bindings for pgvector, the open-source vector similarity search extension for PostgreSQL | [github.com/pgvector/pgvector-php](https://github.com/pgvector/pgvector-php) |
| Laravel Octane | High-performance application server for Laravel; recommended for streaming AI responses | [laravel.com/docs/12.x/octane](https://laravel.com/docs/12.x/octane) |
| Laravel Horizon | Dashboard and configuration for Redis-powered queues; essential for monitoring AI job processing | [laravel.com/docs/12.x/horizon](https://laravel.com/docs/12.x/horizon) |

## E.4 AI Provider Documentation

| Provider | Documentation | API Reference |
|---|---|---|
| OpenAI | [platform.openai.com/docs](https://platform.openai.com/docs) | Models, pricing, rate limits |
| Anthropic | [docs.anthropic.com](https://docs.anthropic.com) | Claude models, usage guides |
| Google AI (Gemini) | [ai.google.dev/docs](https://ai.google.dev/docs) | Gemini models, multimodal |
| Cohere | [docs.cohere.com](https://docs.cohere.com) | Embeddings, reranking, text |
| ElevenLabs | [elevenlabs.io/docs](https://elevenlabs.io/docs) | TTS, STT, voice cloning |
| Jina AI | [jina.ai/docs](https://jina.ai/docs) | Embeddings, reranking |
| VoyageAI | [docs.voyageai.com](https://docs.voyageai.com) | Embeddings, reranking |
| Mistral | [docs.mistral.ai](https://docs.mistral.ai) | Models, embeddings, fine-tuning |
| Groq | [console.groq.com/docs](https://console.groq.com/docs) | Ultra-fast inference |
| xAI | [docs.x.ai](https://docs.x.ai) | Grok models, image generation |
| DeepSeek | [platform.deepseek.com/docs](https://platform.deepseek.com/docs) | Reasoning models |
| Ollama | [ollama.com/docs](https://ollama.com/docs) | Self-hosted, local inference |

## E.5 Conference Talks and Videos

| Talk | Event | Topic |
|---|---|---|
| Taylor Otwell — Laravel AI SDK Keynote | Laracon India 2026 | Official announcement and demo of the Laravel AI SDK |
| Taylor Otwell — Laravel MCP | Laracon India 2026 | Introduction to Model Context Protocol support in Laravel |
| Nuno Maduro — AI-Powered Testing | Laracon EU 2026 | Testing patterns for AI features in Laravel |
| Laracon Online Sessions | Laracon Online 2026 | Various community talks on AI integration patterns |

## E.6 Foundational AI Concepts

For readers who want to deepen their understanding of the AI concepts underlying the SDK:

| Topic | Recommended Resource |
|---|---|
| How LLMs Work | Andrej Karpathy — "Intro to Large Language Models" (YouTube) |
| Embeddings Explained | Jay Alammar — "The Illustrated Word2Vec" (jalammar.github.io) |
| RAG Architecture | Lewis et al. — "Retrieval-Augmented Generation for Knowledge-Intensive NLP Tasks" (arxiv.org) |
| Vector Databases | pgvector documentation and the Pinecone learning center |
| Prompt Engineering | OpenAI Prompt Engineering Guide (platform.openai.com/docs/guides/prompt-engineering) |
| AI Agents | Anthropic — "Building Effective Agents" (anthropic.com) |
| Model Context Protocol | MCP Specification (modelcontextprotocol.io) |

## E.7 Staying Current

The AI landscape evolves rapidly. To stay current with Laravel AI developments:

1. **Star the repositories**: Watch [github.com/laravel/ai](https://github.com/laravel/ai) and [github.com/laravel/mcp](https://github.com/laravel/mcp) for release notifications.
2. **Follow Laravel News**: The editorial team covers SDK updates promptly.
3. **Join the Laravel Discord**: The `#ai` channel is active with SDK discussions and community support.
4. **Subscribe to provider changelogs**: Each AI provider publishes model updates and API changes that may affect your applications.
5. **Test against canary releases**: Use `composer require laravel/ai:dev-main` in a staging environment to preview upcoming features before they land in stable releases.

---

*This concludes the appendices. For errata, updates, and supplementary materials, visit the book's companion repository.*

*Laravel is created and maintained by Taylor Otwell. The Laravel AI SDK and Laravel MCP are official Laravel packages built by Taylor Otwell and their respective contributor communities. This book is an independent community guide and is not affiliated with or endorsed by Laravel LLC.*
