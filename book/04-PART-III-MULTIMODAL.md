# Part III — Multimodal AI

The first two parts of this book focused on text: prompting agents, structuring their output, giving them tools, and managing conversations. But modern AI is not limited to words. The models behind OpenAI, Google Gemini, xAI, and ElevenLabs can generate images from descriptions, synthesize human-sounding speech, and transcribe audio recordings into text — all through the same elegant interface the Laravel AI SDK provides for agents.

Part III explores these multimodal capabilities. Chapter 8 covers image generation, from simple prompts to remixed compositions stored on your filesystem. Chapter 9 introduces audio: converting text to speech with fine-grained voice control and transcribing recordings back to text with speaker diarization. Chapter 10 ties it all together with the SDK's unified file handling system — the glue that lets you move documents, images, and audio between your application, your storage disks, and your AI providers.

By the end of Part III, you will be able to build applications that see, speak, listen, and read — not just think.

---

## Chapter 8: Image Generation

Every web application eventually needs images. Product thumbnails, social media cards, hero illustrations, avatar placeholders — the list is endless. Traditionally, you would commission a designer, license stock photography, or learn a tool like Photoshop. The Laravel AI SDK collapses all of that into a single class: `Laravel\Ai\Image`.

With one line of code, you can describe what you want and get a generated image back. With a few more lines, you can control the aspect ratio, quality, and even remix existing images into new compositions. And because this is Laravel, you can store the result on any filesystem disk, queue the generation for background processing, and test everything without hitting a real API.

### 8.1 Generating Images with Laravel

The `Image` class is the entry point for all image generation. Its API follows the same fluent pattern you have seen throughout the SDK:

```php
use Laravel\Ai\Image;

$image = Image::of('A rustic wooden table with fresh sourdough bread, olive oil, and rosemary')
    ->generate();
```

The `generate()` method sends the prompt to your configured image provider and returns an `ImageResponse` object. To access the raw binary content of the generated image — useful for inline responses, streaming to the browser, or manual file operations — cast the response to a string:

```php
$rawContent = (string) $image;

return response($rawContent, 200, [
    'Content-Type' => 'image/png',
]);
```

You can also specify a provider and model at generation time, overriding whatever is configured in `config/ai.php`:

```php
use Laravel\Ai\Enums\Lab;

$image = Image::of('A neon-lit Tokyo street at night, cyberpunk aesthetic')
    ->generate(provider: Lab::xAI);
```

The SDK currently supports three providers for image generation:

| Provider | Lab Enum     | Notable Models                    |
|----------|--------------|-----------------------------------|
| OpenAI   | `Lab::OpenAI`  | DALL·E 3, gpt-image-1           |
| Gemini   | `Lab::Gemini`  | Imagen 3, Gemini native imaging |
| xAI      | `Lab::xAI`     | Grok image generation           |

If you want automatic failover between providers — so that a rate limit or outage on one provider transparently falls through to another — pass an array:

```php
$image = Image::of('A minimalist logo for a coffee shop called "Drip"')
    ->generate(provider: [Lab::OpenAI, Lab::Gemini, Lab::xAI]);
```

The SDK will try each provider in order until one succeeds.

### 8.2 Aspect Ratios, Quality, and Timeouts

Not every image is a square. Social media banners need landscape orientation. Mobile app splash screens need portrait. The `Image` class provides three aspect ratio methods that map to provider-appropriate dimensions:

```php
$square = Image::of('A perfectly symmetrical mandala pattern')
    ->square()
    ->generate();

$portrait = Image::of('A full-length fashion illustration of a woman in a red dress')
    ->portrait()
    ->generate();

$landscape = Image::of('A panoramic mountain range at sunset with dramatic clouds')
    ->landscape()
    ->generate();
```

Each provider interprets these aspect ratios according to its own supported dimensions. You do not need to memorize pixel values — the SDK handles the translation.

Quality controls how much computational effort the provider spends on the image. Higher quality means more detail and coherence, but also longer generation times and potentially higher cost:

```php
$image = Image::of('A photorealistic macro shot of morning dew on a spider web')
    ->quality('high')
    ->landscape()
    ->generate();
```

The `quality()` method accepts three values: `'high'`, `'medium'`, and `'low'`. The default varies by provider, but `'medium'` is typical.

Image generation can be slow — some providers take 30 seconds or more for high-quality outputs. If you need to extend the HTTP timeout beyond the default, use the `timeout()` method:

```php
$image = Image::of('An intricate architectural blueprint of a Gothic cathedral')
    ->quality('high')
    ->timeout(120)
    ->generate();
```

The timeout is specified in seconds. This is particularly important in controller contexts where you want to avoid gateway timeouts.

Here is a complete example combining all options:

```php
use Laravel\Ai\Image;
use Laravel\Ai\Enums\Lab;

$image = Image::of('A watercolor painting of a Venetian canal at golden hour')
    ->landscape()
    ->quality('high')
    ->timeout(90)
    ->generate(provider: Lab::Gemini);

return response((string) $image, 200, [
    'Content-Type' => 'image/png',
    'Content-Disposition' => 'inline; filename="venice.png"',
]);
```

### 8.3 Image Remixing with Attachments

Sometimes you do not want to generate an image from scratch — you want to transform an existing one. The SDK calls this *remixing*. By attaching one or more reference images to your prompt, you give the AI model visual context to work with:

```php
use Laravel\Ai\Image;
use Laravel\Ai\Files;

$image = Image::of('Transform this photo into a Studio Ghibli anime style illustration')
    ->attachments([
        Files\Image::fromStorage('photos/original-landscape.jpg'),
    ])
    ->landscape()
    ->generate();
```

The `attachments()` method accepts an array of image sources. The SDK supports multiple ways to provide reference images, matching the same patterns you use for agent attachments:

```php
use Laravel\Ai\Files;

$image = Image::of('Combine these two photos into a single composition with a sunset sky')
    ->attachments([
        // From Laravel's filesystem (Storage facade)
        Files\Image::fromStorage('photos/foreground.jpg'),

        // From an absolute path on the server
        Files\Image::fromPath('/var/uploads/background.jpg'),

        // From a remote URL
        Files\Image::fromUrl('https://example.com/images/sky-reference.jpg'),

        // From a user upload in the current request
        $request->file('reference_image'),
    ])
    ->landscape()
    ->quality('high')
    ->generate();
```

This is powerful for building features like:

- **Style transfer**: "Make this product photo look hand-drawn"
- **Background replacement**: "Put this person in front of the Eiffel Tower"
- **Variation generation**: "Create three variations of this logo with different color schemes"
- **Image editing**: "Remove the background from this photo and add a gradient"

Here is a practical controller that accepts a user upload and generates a stylized version:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Ai\Image;
use Laravel\Ai\Files;

class ImageRemixController extends Controller
{
    public function remix(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'style' => 'required|string|max:200',
        ]);

        $image = Image::of("Apply this artistic style: {$request->style}")
            ->attachments([
                $request->file('image'),
            ])
            ->square()
            ->quality('high')
            ->timeout(120)
            ->generate();

        $path = $image->storePubliclyAs(
            'remixed/' . uniqid() . '.png'
        );

        return response()->json([
            'url' => Storage::url($path),
        ]);
    }
}
```

### 8.4 Storing Generated Images

Generating an image is only useful if you can save it. The `ImageResponse` object provides four storage methods that mirror Laravel's `UploadedFile` API:

```php
$image = Image::of('A minimalist icon set for a productivity app');

// Store with an auto-generated filename on the default disk
$path = $image->store();

// Store with a specific filename
$path = $image->storeAs('icons/productivity-set.png');

// Store with public visibility (e.g., for S3)
$path = $image->storePublicly();

// Store with public visibility and a specific filename
$path = $image->storePubliclyAs('icons/productivity-set.png');
```

Each method returns the path where the file was saved, relative to the disk root. The `storePublicly` variants set the file's visibility to `public`, which matters when you are using cloud disks like Amazon S3 or Google Cloud Storage where files are private by default.

A common pattern is to generate an image, store it, and then save the path to a database record:

```php
use App\Models\Product;
use Laravel\Ai\Image;

$product = Product::find($id);

$path = Image::of("A professional product photo of: {$product->name}. {$product->description}")
    ->square()
    ->quality('high')
    ->storePubliclyAs("products/{$product->id}/hero.png");

$product->update(['hero_image' => $path]);
```

### 8.5 Queued Image Generation

Image generation is inherently slow. In a web request context, you rarely want the user staring at a loading spinner for 30 seconds. The SDK lets you push image generation onto a queue and handle the result asynchronously:

```php
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

Image::of('An isometric illustration of a modern co-working space')
    ->portrait()
    ->quality('high')
    ->queue()
    ->then(function (ImageResponse $image) {
        $path = $image->storePubliclyAs('illustrations/coworking.png');

        // Notify the user, update a database record, broadcast an event...
        Workspace::find(1)->update(['illustration' => $path]);
    });
```

The `queue()` method dispatches the generation to your queue worker. The `then()` callback receives the completed `ImageResponse` once the worker finishes. You can also add error handling:

```php
Image::of('A surrealist landscape inspired by Salvador Dalí')
    ->landscape()
    ->queue()
    ->then(function (ImageResponse $image) {
        $image->storeAs('art/dali-landscape.png');
    })
    ->catch(function (\Throwable $e) {
        Log::error('Image generation failed', ['error' => $e->getMessage()]);
    });
```

Queued generation is especially useful for bulk operations. Imagine an e-commerce application that generates product images for an entire catalog:

```php
use App\Models\Product;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

$products = Product::whereNull('ai_image')->get();

foreach ($products as $product) {
    Image::of("Professional product photography of {$product->name} on a white background")
        ->square()
        ->quality('high')
        ->queue()
        ->then(function (ImageResponse $image) use ($product) {
            $path = $image->storePubliclyAs("products/{$product->id}.png");
            $product->update(['ai_image' => $path]);
        });
}
```

Each image generation is dispatched as a separate job, so your queue workers can process them in parallel.

---

## Chapter 9: Audio — Text-to-Speech and Transcription

The web is not just visual. Podcasts, audiobooks, voice assistants, accessibility features, meeting transcripts — audio is everywhere. The Laravel AI SDK provides two complementary audio capabilities: **Text-to-Speech (TTS)** for generating spoken audio from text, and **Speech-to-Text (STT)** for transcribing recordings back into written form.

### 9.1 Generating Speech from Text (TTS)

The `Audio` class converts text into spoken audio. Its API will feel instantly familiar:

```php
use Laravel\Ai\Audio;

$audio = Audio::of('Welcome to our application. Let me walk you through the features.')
    ->generate();
```

The `generate()` method sends the text to your configured TTS provider and returns an `AudioResponse` object. Like images, you can cast it to a string to access the raw audio data:

```php
$rawContent = (string) $audio;

return response($rawContent, 200, [
    'Content-Type' => 'audio/mpeg',
]);
```

Two providers support text-to-speech:

| Provider   | Lab Enum          | Strengths                              |
|------------|-------------------|----------------------------------------|
| OpenAI     | `Lab::OpenAI`     | Fast, natural-sounding, multiple voices |
| ElevenLabs | `Lab::ElevenLabs` | Ultra-realistic, voice cloning, 29+ languages |

You can specify the provider at generation time:

```php
$audio = Audio::of('This is generated with ElevenLabs for ultra-realistic speech.')
    ->generate(provider: Lab::ElevenLabs);
```

### 9.2 Voices, Gender, and Instructions

The default voice is provider-dependent, but the SDK gives you several ways to control it. The simplest approach uses gender selection:

```php
$audio = Audio::of('Good morning! Your daily briefing is ready.')
    ->female()
    ->generate();

$audio = Audio::of('Breaking news: Laravel 12 has been released.')
    ->male()
    ->generate();
```

The `female()` and `male()` methods select an appropriate voice from the provider's catalog. For more precise control, use the `voice()` method with a provider-specific voice ID or name:

```php
// OpenAI voice names
$audio = Audio::of('Let me tell you a story.')
    ->voice('nova')
    ->generate();

// ElevenLabs voice IDs
$audio = Audio::of('Premium voice synthesis at its finest.')
    ->voice('21m00Tcm4TlvDq8ikWAM')
    ->generate(provider: Lab::ElevenLabs);
```

Perhaps the most creative feature is the `instructions()` method, which lets you describe *how* the text should be spoken. This goes beyond simple voice selection — it controls tone, pacing, emotion, and character:

```php
$audio = Audio::of('Ahoy! Welcome aboard, matey. Let me show ye the treasure map.')
    ->male()
    ->instructions('Spoken like a grizzled pirate captain with a deep, gravelly voice')
    ->generate();
```

The `instructions()` method is remarkably flexible. Here are some practical examples:

```php
// Meditation app
$audio = Audio::of($meditationScript)
    ->female()
    ->instructions('Calm, slow, soothing. Long pauses between sentences. Whisper-like quality.')
    ->generate();

// News anchor
$audio = Audio::of($newsArticle)
    ->male()
    ->instructions('Professional news anchor tone. Clear enunciation, steady pace, authoritative.')
    ->generate();

// Children's story
$audio = Audio::of($storyText)
    ->female()
    ->instructions('Warm, animated storytelling voice. Expressive with different character voices.')
    ->generate();

// Podcast intro
$audio = Audio::of('Welcome back to Laravel Unplugged, the podcast for artisan developers.')
    ->male()
    ->instructions('Upbeat, energetic radio host. Slight smile in the voice.')
    ->generate();
```

> **Note**: Not all providers support instructions equally. OpenAI's newer models handle them well; ElevenLabs relies more on its voice selection and settings. Test with your chosen provider to calibrate expectations.

### 9.3 Storing Generated Audio

Audio storage works identically to image storage. The `AudioResponse` object provides the same four methods:

```php
$audio = Audio::of('This is a test of the emergency broadcast system.')
    ->female()
    ->generate();

// Auto-generated filename on default disk
$path = $audio->store();

// Specific filename
$path = $audio->storeAs('broadcasts/emergency-test.mp3');

// Public visibility
$path = $audio->storePublicly();

// Public visibility with specific filename
$path = $audio->storePubliclyAs('broadcasts/emergency-test.mp3');
```

A realistic use case is generating audio narrations for blog posts:

```php
namespace App\Http\Controllers;

use App\Models\Article;
use Laravel\Ai\Audio;

class ArticleNarrationController extends Controller
{
    public function generate(Article $article)
    {
        $path = Audio::of($article->body)
            ->female()
            ->instructions('Professional narrator. Clear, measured pace suitable for a tech article.')
            ->storePubliclyAs("narrations/{$article->slug}.mp3");

        $article->update(['narration_path' => $path]);

        return back()->with('success', 'Narration generated successfully.');
    }
}
```

### 9.4 Transcribing Audio to Text (STT)

The other side of audio is transcription — converting spoken words into text. The `Transcription` class handles this, and it accepts audio from multiple sources:

```php
use Laravel\Ai\Transcription;

// From a local file path
$transcript = Transcription::fromPath('/var/recordings/meeting-2026-02-23.mp3')
    ->generate();

// From Laravel's filesystem
$transcript = Transcription::fromStorage('recordings/interview.wav')
    ->generate();

// From a user upload
$transcript = Transcription::fromUpload($request->file('audio'))
    ->generate();
```

The `generate()` method returns a `TranscriptionResponse` that can be cast to a string:

```php
$text = (string) $transcript;
```

Three providers support transcription:

| Provider   | Lab Enum          | Strengths                           |
|------------|-------------------|-------------------------------------|
| OpenAI     | `Lab::OpenAI`     | Whisper model, fast, accurate       |
| ElevenLabs | `Lab::ElevenLabs` | High accuracy, multiple languages   |
| Mistral    | `Lab::Mistral`    | Competitive accuracy, EU hosting    |

Here is a complete controller for transcribing uploaded audio:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Ai\Transcription;

class TranscriptionController extends Controller
{
    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:25600',
        ]);

        $transcript = Transcription::fromUpload($request->file('audio'))
            ->generate();

        return response()->json([
            'text' => (string) $transcript,
        ]);
    }
}
```

### 9.5 Speaker Diarization

In many real-world recordings — meetings, interviews, podcasts — multiple people are speaking. Standard transcription produces a single block of text with no indication of who said what. **Diarization** solves this by segmenting the transcript by speaker.

The SDK makes diarization a single method call:

```php
$transcript = Transcription::fromStorage('meetings/standup-2026-02-23.mp3')
    ->diarize()
    ->generate();
```

The diarized output identifies speaker changes, making it dramatically more useful for applications like:

- **Meeting minutes**: Automatically attribute statements to participants
- **Interview processing**: Separate interviewer questions from interviewee answers
- **Podcast show notes**: Generate per-speaker summaries
- **Customer support analysis**: Distinguish agent from customer in call recordings

Here is a practical example that transcribes a meeting and feeds the result to an agent for summarization:

```php
use Laravel\Ai\Transcription;
use App\Ai\Agents\MeetingSummarizer;

$transcript = Transcription::fromStorage('meetings/weekly-sync.mp3')
    ->diarize()
    ->generate();

$summary = (new MeetingSummarizer)->prompt(
    "Summarize this meeting transcript, noting key decisions and action items:\n\n" . (string) $transcript
);
```

### 9.6 Queued Audio Processing

Both TTS and transcription can be time-consuming operations. Long text passages may take several seconds to synthesize, and large audio files can take even longer to transcribe. The SDK supports queuing for both.

**Queued text-to-speech:**

```php
use Laravel\Ai\Audio;
use Laravel\Ai\Responses\AudioResponse;

Audio::of($article->body)
    ->female()
    ->instructions('Professional narrator')
    ->queue()
    ->then(function (AudioResponse $audio) use ($article) {
        $path = $audio->storePubliclyAs("narrations/{$article->slug}.mp3");
        $article->update(['narration_path' => $path]);
    })
    ->catch(function (\Throwable $e) {
        Log::error('TTS generation failed', ['error' => $e->getMessage()]);
    });
```

**Queued transcription:**

```php
use Laravel\Ai\Transcription;
use Laravel\Ai\Responses\TranscriptionResponse;

Transcription::fromStorage('recordings/long-interview.mp3')
    ->diarize()
    ->queue()
    ->then(function (TranscriptionResponse $transcript) {
        Meeting::create([
            'transcript' => (string) $transcript,
            'recorded_at' => now(),
        ]);
    });
```

Queued processing is the recommended approach for production applications. It keeps your HTTP responses fast and lets your queue workers handle the heavy lifting.

---

## Chapter 10: Attachments and File Handling

Throughout Parts II and III, you have seen files appear in various contexts: documents attached to agent prompts, images attached to remix operations, audio files fed into transcription. Behind all of these interactions is the SDK's unified file handling system, built around two core classes: `Laravel\Ai\Files\Document` and `Laravel\Ai\Files\Image`.

This chapter examines the file system in depth. You will learn every way to create file references, how to store files with AI providers for later retrieval, how to name and organize them, and how to build workflows that bridge your local storage with provider-hosted file systems.

### 10.1 The Document and Image Classes

The SDK provides two file classes, each tailored to its content type:

```php
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;
```

`Document` handles text-based files: PDFs, Markdown, plain text, Word documents, CSVs, and similar formats. `Image` handles visual files: JPEG, PNG, WebP, GIF, and other image formats.

Both classes share the same set of factory methods for creating file references. The difference is semantic — it tells the SDK (and the AI provider) what kind of content to expect, which affects how the file is processed and interpreted.

### 10.2 Creating File References

The SDK supports six ways to create a file reference, covering every source you might encounter in a Laravel application.

#### From the Local Filesystem

Use `fromPath()` when you have an absolute path to a file on your server:

```php
$doc = Document::fromPath('/var/data/reports/quarterly-2026-q1.pdf');
$img = Image::fromPath('/var/uploads/photos/product-shot.jpg');
```

#### From Laravel Storage Disks

Use `fromStorage()` to reference files managed by Laravel's `Storage` facade. You can optionally specify the disk:

```php
// Default disk
$doc = Document::fromStorage('reports/quarterly.pdf');

// Specific disk
$doc = Document::fromStorage('reports/quarterly.pdf', disk: 's3');
$img = Image::fromStorage('photos/hero.jpg', disk: 'public');
```

This is the most common source in Laravel applications, since most files pass through the Storage system at some point.

#### From Remote URLs

Use `fromUrl()` to reference files hosted on external servers:

```php
$doc = Document::fromUrl('https://example.com/whitepapers/ai-strategy-2026.pdf');
$img = Image::fromUrl('https://cdn.example.com/images/product-banner.jpg');
```

The SDK will fetch the content when the file is needed. This is useful for processing content that lives outside your application — customer-provided links, third-party APIs, public datasets.

#### From Raw String Content

Use `fromString()` when you have file content in memory and need to wrap it as a file reference. You must provide the MIME type so the SDK knows how to handle it:

```php
$csvContent = "name,email,plan\nAlice,alice@example.com,pro\nBob,bob@example.com,free";
$doc = Document::fromString($csvContent, 'text/csv');

$markdownContent = "# Meeting Notes\n\n- Decided on Laravel AI SDK\n- Launch target: March 2026";
$doc = Document::fromString($markdownContent, 'text/markdown');
```

This is particularly useful when generating content programmatically — building CSV exports, composing Markdown documents, or assembling data from database queries.

#### From User Uploads

Use `fromUpload()` to wrap an `UploadedFile` instance from an HTTP request:

```php
$doc = Document::fromUpload($request->file('document'));
$img = Image::fromUpload($request->file('photo'));
```

This integrates naturally with Laravel's request validation:

```php
public function analyze(Request $request)
{
    $request->validate([
        'contract' => 'required|file|mimes:pdf|max:10240',
    ]);

    $response = (new ContractAnalyzer)->prompt(
        'Review this contract and identify potential risks.',
        attachments: [
            Document::fromUpload($request->file('contract')),
        ]
    );

    return response()->json(['analysis' => (string) $response]);
}
```

Note that you can also pass `$request->file('photo')` directly as an attachment without wrapping it — the SDK accepts `UploadedFile` instances natively in attachment arrays.

#### From a Provider File ID

Use `fromId()` to reference a file that has already been stored with an AI provider (covered in the next section):

```php
$doc = Document::fromId('file-abc123');
$img = Image::fromId('file-xyz789');
```

This avoids re-uploading files that the provider already has. It is the key to efficient workflows where you store a file once and reference it across multiple prompts, agents, or vector stores.

### 10.3 Storing Files with AI Providers

AI providers like OpenAI, Anthropic, and Gemini maintain their own file storage systems. When you store a file with a provider, it lives on their servers and can be referenced by ID in future API calls — without re-uploading the content each time.

The `put()` method stores a file with the configured default provider:

```php
use Laravel\Ai\Files\Document;

$stored = Document::fromPath('/var/data/manual.pdf')->put();

echo $stored->id; // "file-abc123"
```

The returned object contains the provider-assigned file ID, which you should save to your database for future reference.

You can target a specific provider:

```php
use Laravel\Ai\Enums\Lab;

$stored = Document::fromStorage('contracts/nda.pdf')
    ->put(provider: Lab::Anthropic);
```

Images work the same way:

```php
use Laravel\Ai\Files\Image;

$stored = Image::fromUpload($request->file('photo'))
    ->put(provider: Lab::OpenAI);
```

Here is a practical pattern: a controller that accepts document uploads, stores them with a provider, and saves the reference to the database:

```php
namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Laravel\Ai\Files\Document;

class KnowledgeBaseController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,md,txt|max:20480',
            'title' => 'required|string|max:255',
        ]);

        $stored = Document::fromUpload($request->file('document'))->put();

        KnowledgeDocument::create([
            'title' => $request->title,
            'provider_file_id' => $stored->id,
            'original_filename' => $request->file('document')->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Document uploaded to knowledge base.');
    }
}
```

### 10.4 Naming Files

When storing files with a provider, you can assign a human-readable name using the `as()` method. This helps with organization and identification when managing files through provider dashboards or APIs:

```php
$stored = Document::fromString('Hello, World!', 'text/plain')
    ->as('hello.txt')
    ->put();

$stored = Document::fromStorage('reports/q1-2026.pdf')
    ->as('Q1 2026 Financial Report.pdf')
    ->put();
```

The `as()` method sets the filename that the provider will associate with the stored file. Without it, the provider typically assigns a generic name or derives one from the source.

### 10.5 Retrieving and Inspecting Stored Files

Once a file is stored with a provider, you can retrieve its metadata using `fromId()` combined with `get()`:

```php
use Laravel\Ai\Files\Document;

$file = Document::fromId('file-abc123')->get();

$file->id;         // "file-abc123"
$file->mimeType(); // "application/pdf"
```

The `get()` method fetches the file's metadata from the provider. This is useful for verifying that a file still exists, checking its type before using it, or displaying file information in your UI.

### 10.6 Deleting Stored Files

When a file is no longer needed, delete it from the provider to free up storage and maintain good hygiene:

```php
Document::fromId('file-abc123')->delete();
```

A complete lifecycle example — upload, use, and clean up:

```php
use Laravel\Ai\Files\Document;
use App\Ai\Agents\ContractReviewer;

// Store the file
$stored = Document::fromUpload($request->file('contract'))->put();

// Use it in a prompt
$response = (new ContractReviewer)->prompt(
    'Review this contract for compliance issues.',
    attachments: [
        Document::fromId($stored->id),
    ]
);

// Clean up if no longer needed
Document::fromId($stored->id)->delete();

return response()->json(['review' => (string) $response]);
```

### 10.7 Using Stored Files in Conversations

The real power of provider-stored files emerges in multi-turn conversations. Instead of re-uploading a document with every prompt, you store it once and reference it by ID throughout the conversation:

```php
use Laravel\Ai\Files\Document;
use App\Ai\Agents\ResearchAssistant;
use App\Models\ResearchDocument;

// The user uploads a research paper once
$stored = Document::fromUpload($request->file('paper'))->put();

ResearchDocument::create([
    'user_id' => $request->user()->id,
    'file_id' => $stored->id,
    'title' => $request->title,
]);

// First prompt: summarize the paper
$response = (new ResearchAssistant)
    ->forUser($request->user())
    ->prompt(
        'Summarize the key findings of this research paper.',
        attachments: [
            Document::fromId($stored->id),
        ]
    );

// Later, in another request: ask follow-up questions about the same paper
$document = ResearchDocument::where('user_id', $user->id)->latest()->first();

$response = (new ResearchAssistant)
    ->continue($conversationId, as: $user)
    ->prompt(
        'What methodology did the authors use?',
        attachments: [
            Document::fromId($document->file_id),
        ]
    );
```

This pattern is efficient because:

1. The file is uploaded to the provider exactly once
2. Every subsequent reference uses the lightweight file ID
3. The provider can cache and pre-process the file for faster responses
4. You maintain a clean mapping between your database records and provider files

### 10.8 Combining File Types in Complex Workflows

In real applications, you often need to combine multiple file types in a single workflow. Consider a product listing system that analyzes both images and descriptions:

```php
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;
use App\Ai\Agents\ProductCatalogAgent;

$response = (new ProductCatalogAgent)->prompt(
    'Analyze this product. Generate a compelling description based on the image and the specification sheet.',
    attachments: [
        Image::fromStorage('products/widget-photo.jpg'),
        Document::fromStorage('products/widget-specs.pdf'),
    ]
);
```

Or a compliance system that processes multiple document types:

```php
use Laravel\Ai\Files\Document;
use App\Ai\Agents\ComplianceAuditor;

$attachments = collect($request->file('documents'))->map(function ($file) {
    return Document::fromUpload($file);
})->all();

$response = (new ComplianceAuditor)->prompt(
    'Review all attached documents for regulatory compliance. Flag any issues.',
    attachments: $attachments,
);
```

The file handling system is designed to be the connective tissue between your Laravel application and the AI providers. Whether you are building a simple chatbot that reads uploaded PDFs, an e-commerce platform that generates and stores product images, or a podcast platform that transcribes episodes and generates show notes — the `Document` and `Image` classes provide a consistent, Laravel-native interface for every file operation.

### 10.9 File Handling Reference

Here is a complete reference table for quick lookups:

| Operation       | Document                                          | Image                                          |
|-----------------|---------------------------------------------------|-------------------------------------------------|
| From path       | `Document::fromPath('/path/to/file')`             | `Image::fromPath('/path/to/file')`              |
| From storage    | `Document::fromStorage('file', disk: 'local')`    | `Image::fromStorage('file', disk: 'local')`     |
| From URL        | `Document::fromUrl('https://...')`                | `Image::fromUrl('https://...')`                 |
| From string     | `Document::fromString($content, $mime)`           | `Image::fromString($content, $mime)`            |
| From upload     | `Document::fromUpload($request->file('f'))`       | `Image::fromUpload($request->file('f'))`        |
| From provider ID| `Document::fromId('file-id')`                     | `Image::fromId('file-id')`                      |
| Store           | `->put()` or `->put(provider: Lab::OpenAI)`       | `->put()` or `->put(provider: Lab::OpenAI)`     |
| Name            | `->as('filename.pdf')`                            | `->as('photo.jpg')`                             |
| Retrieve        | `Document::fromId('id')->get()`                   | `Image::fromId('id')->get()`                    |
| Delete          | `Document::fromId('id')->delete()`                | `Image::fromId('id')->delete()`                 |

---

## Summary

Part III has taken you beyond text into the full spectrum of multimodal AI. You can now:

- **Generate images** from natural language descriptions, control their aspect ratio and quality, remix existing images with style transfer, store the results on any Laravel filesystem disk, and queue generation for background processing.

- **Synthesize speech** from text with control over voice gender, specific voice selection, and spoken instructions that shape tone and delivery. You can store the audio and queue long synthesis jobs.

- **Transcribe audio** from files, storage disks, or user uploads — with optional speaker diarization that identifies who said what.

- **Handle files** through a unified system that supports six different sources, stores files with AI providers for efficient reuse, and bridges your local filesystem with provider-hosted storage.

These multimodal capabilities, combined with the agent system from Part II, open up a vast design space. You can build applications that analyze uploaded images, narrate written content, transcribe meetings and summarize them, generate illustrations for blog posts, and much more — all within the familiar Laravel ecosystem.

In Part IV, we turn to another dimension of AI: understanding meaning. You will learn how vector embeddings capture semantic relationships, how to search by meaning rather than keywords, and how to build Retrieval-Augmented Generation (RAG) systems that ground AI responses in your own data.
