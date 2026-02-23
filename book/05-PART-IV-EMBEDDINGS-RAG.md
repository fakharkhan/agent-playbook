# Part IV — Embeddings, Search, and RAG

---

## Chapter 11: Vector Embeddings

If agents are the brain of AI-powered applications, embeddings are the memory. They are the mechanism through which your application *understands* language — not just as strings of characters, but as meaning.

In this chapter, you will learn what embeddings are, how to generate them with the Laravel AI SDK, how to store them efficiently in PostgreSQL, and how to cache them for production performance.

### 11.1 What Are Embeddings?

At their core, embeddings are **dense vector representations of text**. When you pass a sentence like "Napa Valley has great wine" to an embedding model, it returns an array of floating-point numbers — typically 1,536 of them — that encode the semantic meaning of that text in a high-dimensional space.

```
"Napa Valley has great wine" → [0.0231, -0.0142, 0.0089, ..., -0.0034]
```

The critical property of these vectors is that **semantically similar texts produce vectors that are close together**. "California vineyards produce excellent wine" would generate a vector very near to the one above, even though the two sentences share almost no words. Meanwhile, "PHP 8.4 introduced property hooks" would produce a vector pointing in an entirely different direction.

This property is what makes embeddings transformative. Traditional keyword search requires exact or fuzzy matches on the words themselves. Embedding-based search operates on *meaning*, enabling your application to understand user intent even when users phrase things in unexpected ways.

Embeddings power three fundamental capabilities you will build throughout this part of the book:

1. **Semantic search** — find documents by meaning, not keywords
2. **Similarity detection** — identify near-duplicate content or related items
3. **Retrieval-Augmented Generation (RAG)** — give agents access to your private data

### 11.2 Generating Embeddings in Laravel

The Laravel AI SDK provides two clean interfaces for generating embeddings: the `Stringable` macro for single strings and the `Embeddings` class for batch operations.

#### The Stringable Approach

For generating a single embedding, the SDK extends Laravel's `Stringable` class with a `toEmbeddings()` method:

```php
use Illuminate\Support\Str;

$embedding = Str::of('Napa Valley has great wine.')->toEmbeddings();

// $embedding is an array of floats: [0.0231, -0.0142, ...]
```

This is the most concise way to generate embeddings and fits naturally into Laravel's fluent string API. It returns a flat array of floats ready to store in your database.

#### The Embeddings Class for Batch Operations

When you need to generate embeddings for multiple texts at once — during data ingestion, for example — the `Embeddings` class is significantly more efficient than calling `toEmbeddings()` in a loop. A single API call handles the entire batch:

```php
use Laravel\Ai\Embeddings;

$response = Embeddings::for([
    'Napa Valley has great wine.',
    'Laravel is a PHP framework.',
    'PostgreSQL supports vector operations.',
])->generate();

$response->embeddings;
// [
//     [0.0231, -0.0142, ...],   // "Napa Valley has great wine."
//     [0.0187, 0.0293, ...],    // "Laravel is a PHP framework."
//     [0.0054, -0.0321, ...],   // "PostgreSQL supports vector operations."
// ]
```

Each element in the `$response->embeddings` array corresponds to the input text at the same index.

#### Specifying Dimensions and Provider

Different embedding models produce vectors of different sizes. OpenAI's `text-embedding-3-small` defaults to 1,536 dimensions, but you can request a specific dimensionality. You can also select a provider and model explicitly:

```php
$response = Embeddings::for(['Napa Valley has great wine.'])
    ->dimensions(1536)
    ->generate(Lab::OpenAI, 'text-embedding-3-small');
```

Choosing fewer dimensions reduces storage requirements and speeds up similarity queries, at the cost of some precision. For most applications, 1,536 dimensions strikes a good balance. If you are working with very large datasets and need faster queries, experiment with 768 or even 256 dimensions — modern embedding models are surprisingly robust at lower dimensionalities.

### 11.3 PostgreSQL and pgvector Setup

Storing embeddings in a regular database column as JSON would work, but performing similarity searches would be agonizingly slow — every query would require a full table scan, computing distances against every row. The `pgvector` extension for PostgreSQL provides a native `vector` column type and specialized indexes that make similarity queries fast.

#### Enabling the Extension

The Laravel AI SDK provides a schema helper that ensures the extension is installed:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->vector('embedding', dimensions: 1536);
            $table->timestamps();
        });
    }
};
```

The `Schema::ensureVectorExtensionExists()` call executes `CREATE EXTENSION IF NOT EXISTS vector` on your PostgreSQL database. This is idempotent — safe to run multiple times.

#### Creating an HNSW Index

Without an index, pgvector performs exact nearest-neighbor search by scanning every row. For datasets beyond a few thousand rows, you need an approximate nearest-neighbor (ANN) index. The SDK creates an HNSW (Hierarchical Navigable Small World) index when you chain `->index()`:

```php
$table->vector('embedding', dimensions: 1536)->index();
```

HNSW indexes provide sub-linear query performance. A table with a million rows that would take seconds to scan without an index will return results in milliseconds with one. The tradeoff is slightly increased insert time and memory usage, both of which are negligible for most applications.

> **Note**: HNSW indexes are most effective when you know the dimensionality in advance. Always specify `dimensions` in your vector column definition.

### 11.4 Storing Embeddings in Your Database

With the migration in place, your Eloquent model needs a single cast to work with embeddings:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}
```

The `'array'` cast handles serialization and deserialization of the float array to and from the `vector` column. Now you can store embeddings alongside your content:

```php
use App\Models\Document;
use Illuminate\Support\Str;

$content = 'Napa Valley is renowned for its Cabernet Sauvignon wines...';

Document::create([
    'title' => 'Napa Valley Wine Guide',
    'content' => $content,
    'embedding' => Str::of($content)->toEmbeddings(),
]);
```

For bulk ingestion, generate all embeddings in a single batch call and then insert them:

```php
use Laravel\Ai\Embeddings;

$articles = Article::whereNull('embedding')->limit(100)->get();

$texts = $articles->pluck('content')->toArray();

$response = Embeddings::for($texts)->generate();

foreach ($articles as $index => $article) {
    $article->update([
        'embedding' => $response->embeddings[$index],
    ]);
}
```

This pattern is efficient because it makes one API call for up to 100 texts rather than 100 individual calls. For very large datasets, chunk your records into batches of 100 and process each batch sequentially.

### 11.5 Caching Embeddings for Performance

Generating embeddings incurs both latency and cost. If you frequently generate embeddings for the same text — for example, when the same search query is repeated by multiple users — caching eliminates redundant API calls.

#### Global Configuration

Enable caching globally in `config/ai.php`:

```php
'caching' => [
    'embeddings' => [
        'cache' => true,
        'store' => env('CACHE_STORE', 'database'),
    ],
],
```

When enabled, every embedding request is automatically cached. The cache key is derived from the provider, model, dimensions, and the input text, so identical requests always hit the cache while different parameters produce separate entries.

The default cache duration is **30 days**. This is a sensible default because embedding models are updated infrequently — the same text produces the same vector for months at a time.

#### Per-Request Caching

If you prefer not to enable caching globally, you can opt in on individual requests:

```php
$response = Embeddings::for(['Napa Valley has great wine.'])
    ->cache()
    ->generate();
```

To set a custom cache duration:

```php
$response = Embeddings::for(['Napa Valley has great wine.'])
    ->cache(seconds: 3600)
    ->generate();
```

The `Stringable` macro also supports caching:

```php
$embedding = Str::of('Napa Valley has great wine.')->toEmbeddings(cache: true);

$embedding = Str::of('Napa Valley has great wine.')->toEmbeddings(cache: 3600);
```

Caching is particularly valuable for search queries. User queries tend to follow a power-law distribution — a small set of popular queries accounts for the majority of traffic. Caching embeddings for those queries can reduce your API costs and latency dramatically.

#### Supported Providers

The following providers support embedding generation:

| Provider  | Notable Models                    |
|-----------|-----------------------------------|
| OpenAI    | `text-embedding-3-small`, `text-embedding-3-large` |
| Gemini    | `text-embedding-004`              |
| Azure     | Azure-hosted OpenAI models        |
| Cohere    | `embed-english-v3.0`, `embed-multilingual-v3.0` |
| Mistral   | `mistral-embed`                   |
| Jina      | `jina-embeddings-v3`              |
| VoyageAI  | `voyage-3`, `voyage-3-lite`       |

Your choice of provider affects vector dimensionality, multilingual support, and pricing. OpenAI's `text-embedding-3-small` is an excellent default — it is inexpensive, fast, and produces high-quality vectors at 1,536 dimensions.

---

## Chapter 12: Semantic Search and Similarity

With embeddings stored in your database, you can now perform searches that understand meaning rather than matching keywords. This chapter covers the Laravel AI SDK's query builder methods for vector similarity, and walks through practical patterns for building semantic search features.

### 12.1 Beyond Keyword Search

Consider a product catalog where a user searches for "comfortable shoes for walking all day." A traditional `LIKE '%comfortable%'` query would miss products described as "cushioned sneakers for extended wear" — even though they are exactly what the user wants.

Semantic search solves this. When both the product descriptions and the search query are represented as embeddings, the database can find products whose meaning is closest to the query, regardless of the specific words used.

### 12.2 The whereVectorSimilarTo Query

The SDK extends Eloquent's query builder with a `whereVectorSimilarTo` method that encapsulates the entire similarity search workflow:

```php
$documents = Document::query()
    ->whereVectorSimilarTo('embedding', $queryEmbedding, minSimilarity: 0.4)
    ->limit(10)
    ->get();
```

The first argument is the column name, the second is the query vector (an array of floats), and `minSimilarity` sets a threshold — only documents with a cosine similarity above 0.4 are returned. Cosine similarity ranges from 0 (completely unrelated) to 1 (identical meaning).

#### Auto-Embedding Strings

One of the most elegant features of the SDK is that you can pass a raw string instead of a pre-computed embedding:

```php
$documents = Document::query()
    ->whereVectorSimilarTo('embedding', 'best wineries in Napa Valley')
    ->limit(10)
    ->get();
```

The SDK automatically converts the string to an embedding using your configured provider before executing the query. This means you can go from a user's search input to semantic results in a single line of code.

#### Combining with Standard Eloquent Queries

`whereVectorSimilarTo` is a regular query builder method, so you can combine it freely with other Eloquent conditions:

```php
$results = Document::query()
    ->where('category', 'wine')
    ->where('published', true)
    ->whereVectorSimilarTo('embedding', $request->input('query'), minSimilarity: 0.5)
    ->limit(10)
    ->get();
```

This query first narrows the dataset to published wine documents, then finds the most semantically similar results. The HNSW index accelerates the vector comparison, while standard B-tree indexes handle the filtering.

### 12.3 Distance Methods and Indexing

For finer control, the SDK provides three low-level query builder methods that let you customize how vector distances are computed, filtered, and sorted.

#### selectVectorDistance

Add the computed distance as a column in your results:

```php
$documents = Document::query()
    ->select('*')
    ->selectVectorDistance('embedding', $queryEmbedding, as: 'distance')
    ->orderByVectorDistance('embedding', $queryEmbedding)
    ->limit(10)
    ->get();

foreach ($documents as $document) {
    echo "{$document->title}: distance = {$document->distance}";
}
```

The `distance` column gives you fine-grained control over how you present results — for example, displaying a "relevance score" badge or hiding results below a threshold.

#### whereVectorDistanceLessThan

Filter documents whose vector distance falls below a maximum:

```php
$documents = Document::query()
    ->whereVectorDistanceLessThan('embedding', $queryEmbedding, maxDistance: 0.3)
    ->get();
```

Lower distances mean higher similarity. A `maxDistance` of 0.3 is relatively strict — only closely related documents will match.

#### orderByVectorDistance

Sort results from most similar to least similar:

```php
$documents = Document::query()
    ->orderByVectorDistance('embedding', $queryEmbedding)
    ->limit(10)
    ->get();
```

#### Combining All Three

The real power appears when you combine all three methods:

```php
$documents = Document::query()
    ->select('id', 'title', 'content')
    ->selectVectorDistance('embedding', $queryEmbedding, as: 'distance')
    ->whereVectorDistanceLessThan('embedding', $queryEmbedding, maxDistance: 0.3)
    ->orderByVectorDistance('embedding', $queryEmbedding)
    ->limit(10)
    ->get();
```

This gives you full control: select only the columns you need, include the distance for display purposes, filter out loosely related results, and sort by relevance.

### 12.4 Building a Semantic Search Feature

Let us assemble these pieces into a complete search feature. This example builds a document search endpoint for a knowledge base application.

#### The Migration

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('category');
            $table->vector('embedding', dimensions: 1536)->index();
            $table->timestamps();
        });
    }
};
```

#### The Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}
```

#### Ingesting Content

```php
use App\Models\KnowledgeArticle;
use Laravel\Ai\Embeddings;

$articles = [
    ['title' => 'Getting Started with Laravel', 'body' => 'Laravel is a web application framework...', 'category' => 'tutorials'],
    ['title' => 'Understanding Eloquent ORM', 'body' => 'Eloquent provides a beautiful, simple ActiveRecord...', 'category' => 'tutorials'],
    ['title' => 'Deploying to Production', 'body' => 'When deploying your Laravel application...', 'category' => 'operations'],
];

$texts = array_column($articles, 'body');
$response = Embeddings::for($texts)->generate();

foreach ($articles as $index => $article) {
    KnowledgeArticle::create([
        ...$article,
        'embedding' => $response->embeddings[$index],
    ]);
}
```

#### The Search Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['query' => 'required|string|max:500']);

        $results = KnowledgeArticle::query()
            ->select('id', 'title', 'body', 'category')
            ->selectVectorDistance('embedding', $request->input('query'), as: 'distance')
            ->whereVectorSimilarTo('embedding', $request->input('query'), minSimilarity: 0.4)
            ->orderByVectorDistance('embedding', $request->input('query'))
            ->limit(10)
            ->get();

        return view('search.results', compact('results'));
    }
}
```

Users can now search for "how do I set up my database" and find the "Getting Started with Laravel" article, even though the query contains none of the article's keywords. This is the power of semantic search.

#### Practical Similarity Thresholds

Through experimentation, the following similarity thresholds serve as useful guidelines:

| Similarity Score | Interpretation                     | Use Case                    |
|------------------|------------------------------------|-----------------------------|
| > 0.92           | Nearly identical content           | Deduplication               |
| 0.75 – 0.92      | Strongly related                   | "Related articles" features |
| 0.50 – 0.75      | Topically similar                  | Search results              |
| 0.40 – 0.50      | Loosely related                    | Broad discovery             |
| < 0.40           | Likely unrelated                   | Filter out                  |

For content deduplication, finding pairs with similarity above 0.92 reliably identifies near-duplicate documents — articles that have been copied, slightly paraphrased, or auto-generated from the same source.

---

## Chapter 13: Retrieval-Augmented Generation (RAG)

Large language models are powerful, but they have a fundamental limitation: they can only reason about information present in their training data or provided in the prompt. Your proprietary documentation, internal knowledge base, and domain-specific data are invisible to them.

Retrieval-Augmented Generation (RAG) solves this by combining information retrieval with text generation. Instead of relying solely on the model's training, the system first *retrieves* relevant documents from your data, then passes those documents to the model as context alongside the user's question. The model generates its answer grounded in your actual data.

The Laravel AI SDK provides two complementary approaches to RAG: the pgvector-based approach from Chapter 12 (using your own database), and the provider-managed **Vector Stores** covered in this chapter. Vector Stores delegate the storage, indexing, and retrieval of documents to the AI provider itself, which is ideal when you want a managed solution without running pgvector.

### 13.1 Understanding RAG Architecture

A RAG system has three stages:

1. **Ingest**: Documents are uploaded, chunked, and indexed in a vector store
2. **Retrieve**: When a user asks a question, the system finds the most relevant document chunks
3. **Generate**: The retrieved chunks are passed to an LLM alongside the question, and the model produces a grounded answer

The Laravel AI SDK's `Stores` class handles ingestion and storage. The `FileSearch` provider tool handles retrieval. And your agent handles generation. Together, they form a complete RAG pipeline.

### 13.2 Vector Stores: Creating and Managing

Vector Stores are provider-managed collections of documents optimized for semantic retrieval.

#### Creating a Store

```php
use Laravel\Ai\Stores;

$store = Stores::create('Knowledge Base');

echo $store->id; // "vs_abc123..."
```

You can provide additional configuration at creation time:

```php
$store = Stores::create(
    name: 'Knowledge Base',
    description: 'Documentation and reference materials for the engineering team.',
    expiresWhenIdleFor: days(30),
);
```

The `expiresWhenIdleFor` parameter tells the provider to automatically delete the store if it has not been accessed for the specified duration. This is useful for temporary or session-scoped knowledge bases.

#### Retrieving a Store

```php
$store = Stores::get('vs_abc123');

$store->id;         // "vs_abc123"
$store->name;       // "Knowledge Base"
$store->fileCounts; // ['completed' => 12, 'in_progress' => 0, 'failed' => 0, ...]
$store->ready;      // true (all files processed)
```

The `ready` property is particularly useful when you need to wait for file processing to complete before querying the store. After uploading documents, it may take a few seconds for the provider to chunk and index them.

#### Deleting a Store

```php
Stores::delete('vs_abc123');
```

Or from an existing store instance:

```php
$store = Stores::get('vs_abc123');
$store->delete();
```

Deleting a store removes it and all its file associations from the provider. The underlying files are not deleted unless you explicitly remove them.

### 13.3 Adding Files to Vector Stores

Once you have a store, you can populate it with documents. The SDK supports multiple ways to add files.

#### Adding by File ID

If you have already stored a file with the provider (see Chapter 10), you can add it by ID:

```php
$store = Stores::get('vs_abc123');

$document = $store->add('file_xyz789');
```

#### Adding from Various Sources

You can also upload and add in a single step:

```php
use Laravel\Ai\Files\Document;

$document = $store->add(Document::fromPath('/path/to/user-manual.pdf'));

$document = $store->add(Document::fromStorage('docs/api-reference.md'));

$document = $store->add($request->file('document'));
```

The SDK handles uploading the file to the provider, then associating it with the vector store. The provider automatically chunks the document, generates embeddings for each chunk, and indexes them for retrieval.

Each `add` call returns a document object with useful properties:

```php
$document->id;     // The store-specific document ID
$document->fileId; // The provider-level file ID
```

#### Adding Files with Metadata

Metadata enables you to filter documents at query time, which is essential for multi-tenant applications, access control, and categorical organization:

```php
$store->add(Document::fromPath('/path/to/architecture-guide.pdf'), metadata: [
    'author' => 'Taylor Otwell',
    'department' => 'Engineering',
    'year' => 2026,
    'access_level' => 'internal',
]);

$store->add(Document::fromStorage('release-notes-v12.md'), metadata: [
    'author' => 'Nuno Maduro',
    'department' => 'Engineering',
    'year' => 2026,
    'document_type' => 'release_notes',
]);
```

We will see how to query against this metadata in the next section.

#### Removing Files from a Store

Remove a file's association from the store:

```php
$store->remove('file_xyz789');
```

To also delete the underlying file from the provider entirely:

```php
$store->remove('file_xyz789', deleteFile: true);
```

Use `deleteFile: true` when the file exists solely for this vector store. If the file is shared across multiple stores or used elsewhere, omit it.

### 13.4 The FileSearch Provider Tool

The `FileSearch` tool is a provider-native tool that performs retrieval against your vector stores during an agent's execution. Unlike the `SimilaritySearch` tool from Chapter 6 — which queries your own database — `FileSearch` queries the provider's managed vector store infrastructure.

#### Basic Usage

```php
use Laravel\Ai\Providers\Tools\FileSearch;

class KnowledgeBaseAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a helpful assistant that answers questions using the knowledge base.';
    }

    public function tools(): iterable
    {
        return [
            new FileSearch(stores: ['vs_abc123']),
        ];
    }
}
```

When the agent determines it needs information to answer a question, it automatically invokes the `FileSearch` tool, which retrieves relevant chunks from the vector store and includes them in the model's context.

#### Querying Multiple Stores

You can search across multiple stores simultaneously:

```php
new FileSearch(stores: ['vs_engineering_docs', 'vs_product_specs']);
```

#### Metadata Filtering with Simple Arrays

For straightforward equality filters, pass a `where` array:

```php
new FileSearch(stores: ['vs_abc123'], where: [
    'author' => 'Taylor Otwell',
    'year' => 2026,
]);
```

This restricts retrieval to documents whose metadata matches all specified conditions.

#### Metadata Filtering with FileSearchQuery

For complex filtering logic, use the `FileSearchQuery` closure:

```php
use Laravel\Ai\Providers\Tools\FileSearchQuery;

new FileSearch(stores: ['vs_abc123'], where: fn (FileSearchQuery $query) =>
    $query->where('department', 'Engineering')
        ->whereNot('status', 'draft')
        ->whereIn('document_type', ['guide', 'reference', 'tutorial'])
);
```

The `FileSearchQuery` builder supports:

| Method         | Description                                |
|----------------|--------------------------------------------|
| `where()`      | Exact match on a metadata field            |
| `whereNot()`   | Exclude documents matching a value         |
| `whereIn()`    | Match any value in an array                |

This filtering happens before the semantic search, so it efficiently narrows the search space without scanning irrelevant documents.

### 13.5 Building a Knowledge Base Agent

Let us build a complete knowledge base agent that ingests documentation and answers questions about it. This is the canonical RAG application.

#### Step 1: Create and Populate the Store

```php
use Laravel\Ai\Stores;
use Laravel\Ai\Files\Document;

$store = Stores::create(
    name: 'Product Documentation',
    description: 'Complete product documentation including guides, API reference, and tutorials.',
);

$files = [
    ['path' => '/docs/getting-started.md', 'meta' => ['section' => 'guides', 'audience' => 'beginner']],
    ['path' => '/docs/api-reference.md', 'meta' => ['section' => 'reference', 'audience' => 'developer']],
    ['path' => '/docs/deployment.md', 'meta' => ['section' => 'operations', 'audience' => 'devops']],
    ['path' => '/docs/troubleshooting.md', 'meta' => ['section' => 'support', 'audience' => 'all']],
];

foreach ($files as $file) {
    $store->add(
        Document::fromPath($file['path']),
        metadata: $file['meta'],
    );
}
```

#### Step 2: Create the Agent

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\FileSearch;
use Stringable;

#[Temperature(0.3)]
#[MaxSteps(5)]
class DocumentationAssistant implements Agent, HasTools
{
    use Promptable;

    public function __construct(
        private string $storeId,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a documentation assistant for our product. Your role is to answer
        user questions accurately using the knowledge base.

        Guidelines:
        - Always search the knowledge base before answering
        - Cite specific sections or documents when possible
        - If the knowledge base does not contain relevant information, say so clearly
        - Do not fabricate information that is not in the documents
        - Keep answers concise but complete
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new FileSearch(stores: [$this->storeId]),
        ];
    }
}
```

#### Step 3: Use the Agent

```php
Route::post('/docs/ask', function (Request $request) {
    $request->validate(['question' => 'required|string|max:1000']);

    $agent = new DocumentationAssistant(
        storeId: config('services.ai.docs_store_id'),
    );

    return $agent->stream($request->input('question'));
});
```

A user asking "How do I deploy the application?" will trigger the following flow:

1. The agent receives the question
2. It invokes the `FileSearch` tool against the vector store
3. The provider retrieves relevant chunks from `deployment.md` and possibly `troubleshooting.md`
4. The agent generates an answer grounded in those specific document chunks
5. The response streams back to the user

This is RAG in action. The agent's answer is grounded in your actual documentation, not in the model's potentially stale training data. When you update your documentation, you update the vector store, and the agent's answers immediately reflect the changes.

---

## Chapter 14: Document Reranking

Semantic search and vector similarity are powerful, but they are not perfect. Embedding-based retrieval is a *first-pass* mechanism — it casts a wide net, finding documents that are roughly related to the query. Reranking is the *second pass* that refines those results using a more sophisticated model specifically trained to judge relevance between a query and a document.

Think of it this way: embedding search uses a lightweight model to quickly narrow millions of documents down to a hundred candidates. Reranking uses a heavyweight model to carefully order those hundred candidates by true relevance. The combination reliably outperforms either approach alone.

### 14.1 Why Reranking Improves Results

Embedding models compress an entire document into a single vector. This is efficient but lossy — subtle relevance signals can be missed. Reranking models, by contrast, take both the query and the document as input and produce a fine-grained relevance score. They can identify that a document is relevant because of a specific paragraph buried in the middle, even if the document's overall embedding does not closely match the query.

Reranking is particularly valuable when:

- Your documents are long and cover multiple topics
- User queries are specific or nuanced
- Precision matters more than recall (e.g., top-3 results must be excellent)
- You are building search experiences where result quality directly impacts user satisfaction

### 14.2 Reranking Documents and Collections

The SDK's `Reranking` class provides a fluent interface for reranking arrays of text:

```php
use Laravel\Ai\Reranking;

$response = Reranking::of([
    'Django is a Python web framework.',
    'Laravel is a PHP web application framework.',
    'React is a JavaScript library for building user interfaces.',
    'Flask is a lightweight WSGI web application framework in Python.',
    'Symfony is a set of reusable PHP components and a web framework.',
])->rerank('PHP frameworks');
```

The response is a collection of ranked documents:

```php
$response->first()->document; // "Laravel is a PHP web application framework."
$response->first()->score;    // 0.97
$response->first()->index;    // 1 (position in the original array)

$response[1]->document; // "Symfony is a set of reusable PHP components..."
$response[1]->score;    // 0.89
$response[1]->index;    // 4
```

Each result contains the `document` text, a `score` indicating relevance (higher is better), and the `index` pointing back to the document's position in the original input array.

#### Limiting Results

When you only need the top results:

```php
$response = Reranking::of($documents)
    ->limit(5)
    ->rerank('search query');
```

#### Specifying a Provider

```php
$response = Reranking::of($documents)
    ->limit(5)
    ->rerank('search query', provider: Lab::Cohere);
```

### Reranking Eloquent Collections

The SDK extends Laravel collections with a `rerank` method, making it effortless to rerank query results directly.

#### By a Single Field

```php
$posts = Post::where('published', true)->get();

$reranked = $posts->rerank('body', 'Laravel tutorials');
```

This extracts the `body` field from each post, sends it to the reranking model alongside the query, and returns the collection sorted by relevance.

#### By Multiple Fields

When relevance depends on more than one column:

```php
$reranked = $posts->rerank(['title', 'body'], 'Laravel tutorials');
```

The SDK concatenates the specified fields for each record before sending them to the reranking model.

#### By Closure

For maximum flexibility, pass a closure that constructs the text to rerank:

```php
$reranked = $posts->rerank(
    fn ($post) => $post->title . ': ' . $post->body,
    'Laravel tutorials'
);
```

This is useful when you want to format the text in a specific way, include conditional fields, or combine data from relationships.

#### With Full Options

The collection `rerank` method accepts all available options:

```php
$reranked = $posts->rerank(
    by: 'content',
    query: 'Laravel tutorials',
    limit: 10,
    provider: Lab::Cohere,
);
```

### Supported Providers

| Provider | Notable Models             |
|----------|----------------------------|
| Cohere   | `rerank-english-v3.0`, `rerank-multilingual-v3.0` |
| Jina     | `jina-reranker-v2-base-multilingual` |

Cohere's reranking models are the industry standard. The multilingual variant handles non-English content well, while the English-specific model provides slightly better precision for English-only use cases.

### 14.3 Combining Search with Reranking

The most effective retrieval pipelines combine vector search and reranking in a two-stage process. Vector search retrieves a broad set of candidates quickly, and reranking refines that set for precision.

Here is a complete example:

```php
<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;
use Laravel\Ai\Reranking;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['query' => 'required|string|max:500']);

        $query = $request->input('query');

        $candidates = KnowledgeArticle::query()
            ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.3)
            ->limit(50)
            ->get();

        $reranked = $candidates->rerank(
            by: fn ($article) => $article->title . "\n\n" . $article->body,
            query: $query,
            limit: 10,
        );

        return view('search.results', ['results' => $reranked]);
    }
}
```

This controller executes a two-stage pipeline:

1. **Stage 1 — Broad retrieval**: `whereVectorSimilarTo` with a low similarity threshold (0.3) retrieves up to 50 candidate articles. The low threshold ensures we do not miss potentially relevant documents.

2. **Stage 2 — Precision reranking**: The candidates are reranked against the query using a dedicated reranking model. Only the top 10 are returned. The reranking model can catch subtle relevance signals that the embedding search missed.

The result is a search experience that is both fast (vector search narrows millions of rows to 50 in milliseconds) and precise (reranking ensures the top 10 are truly the most relevant).

#### When to Use Each Approach

| Approach              | Best For                                      |
|-----------------------|-----------------------------------------------|
| Vector search only    | High-throughput, latency-sensitive applications |
| Reranking only        | Small document sets (< 1,000)                  |
| Search + reranking    | Production search where quality matters         |

For most production applications, the two-stage approach is the right choice. The additional latency from the reranking call (typically 100–300ms) is well worth the improvement in result quality.

#### Feeding Reranked Results into an Agent

You can combine reranking with agent-based generation for a complete RAG pipeline that uses your own database instead of provider-managed vector stores:

```php
use App\Ai\Agents\QuestionAnswerer;
use App\Models\KnowledgeArticle;

$query = 'How do I configure database connections in Laravel?';

$candidates = KnowledgeArticle::query()
    ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.3)
    ->limit(30)
    ->get();

$topArticles = $candidates->rerank(
    by: 'body',
    query: $query,
    limit: 5,
);

$context = $topArticles->map(fn ($article) =>
    "## {$article->title}\n\n{$article->body}"
)->implode("\n\n---\n\n");

$response = (new QuestionAnswerer)->prompt(
    "Using the following documentation, answer this question: {$query}\n\n{$context}"
);
```

This pattern gives you full control over every stage of the pipeline — retrieval, reranking, context assembly, and generation — while keeping each stage clean and testable.

---

## Part IV Summary

You have now mastered the four pillars of intelligent information retrieval in Laravel:

1. **Embeddings** transform text into semantic vectors that capture meaning, enabling your application to understand language at a deeper level than keyword matching

2. **Semantic search** uses those embeddings to find documents by meaning, with `whereVectorSimilarTo` providing a single-line interface to powerful vector queries

3. **RAG** combines retrieval with generation, grounding your agents' answers in your actual data through either pgvector-based search or provider-managed Vector Stores

4. **Reranking** refines search results with precision, ensuring your users see the most relevant results first

Together, these capabilities transform a standard Laravel application into an intelligent system that can search, understand, and reason about your data. In the next part, we will explore how to deliver these AI-powered features to users in real time with streaming, broadcasting, and production resilience patterns.
