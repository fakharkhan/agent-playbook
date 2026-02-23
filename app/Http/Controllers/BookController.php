<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

class BookController extends Controller
{
    protected array $chapters = [
        'preface' => [
            'file' => '01-PREFACE.md',
            'title' => 'Preface',
            'part' => 'Front Matter',
        ],
        'part-i-foundation' => [
            'file' => '02-PART-I-FOUNDATION.md',
            'title' => 'Part I — The Foundation',
            'part' => 'Part I',
            'chapters' => ['Chapter 1: The AI Revolution Meets PHP', 'Chapter 2: Setting Up Your AI Development Environment'],
        ],
        'part-ii-agents' => [
            'file' => '03-PART-II-AGENTS.md',
            'title' => 'Part II — Agents: The Heart of the SDK',
            'part' => 'Part II',
            'chapters' => [
                'Chapter 3: Understanding Agents',
                'Chapter 4: Conversations and Memory',
                'Chapter 5: Structured Output',
                'Chapter 6: Tools — Extending Agent Capabilities',
                'Chapter 7: Agent Configuration and Middleware',
            ],
        ],
        'part-iii-multimodal' => [
            'file' => '04-PART-III-MULTIMODAL.md',
            'title' => 'Part III — Multimodal AI',
            'part' => 'Part III',
            'chapters' => [
                'Chapter 8: Image Generation',
                'Chapter 9: Audio — Text-to-Speech and Transcription',
                'Chapter 10: Attachments and File Handling',
            ],
        ],
        'part-iv-embeddings-rag' => [
            'file' => '05-PART-IV-EMBEDDINGS-RAG.md',
            'title' => 'Part IV — Embeddings, Search, and RAG',
            'part' => 'Part IV',
            'chapters' => [
                'Chapter 11: Vector Embeddings',
                'Chapter 12: Semantic Search and Similarity',
                'Chapter 13: Retrieval-Augmented Generation (RAG)',
                'Chapter 14: Document Reranking',
            ],
        ],
        'part-v-realtime-production' => [
            'file' => '06-PART-V-REALTIME-PRODUCTION.md',
            'title' => 'Part V — Real-Time AI and Production Patterns',
            'part' => 'Part V',
            'chapters' => [
                'Chapter 15: Streaming Responses',
                'Chapter 16: Broadcasting and Queuing',
                'Chapter 17: Failover and Resilience',
                'Chapter 18: Testing AI Features',
            ],
        ],
        'part-vi-vii-mcp-projects' => [
            'file' => '07-PART-VI-VII-MCP-PROJECTS.md',
            'title' => 'Part VI & VII — MCP and Real-World Projects',
            'part' => 'Part VI & VII',
            'chapters' => [
                'Chapter 19: Laravel MCP — Model Context Protocol',
                'Chapter 20: Project — AI-Powered Customer Support Bot',
                'Chapter 21: Project — E-Commerce Product Description Generator',
                'Chapter 22: Project — Multi-Modal Content Platform',
            ],
        ],
        'appendices' => [
            'file' => '09-APPENDICES.md',
            'title' => 'Appendices',
            'part' => 'Appendices',
            'chapters' => [
                'Appendix A: Provider Reference',
                'Appendix B: Complete API Reference',
                'Appendix C: Event Reference',
                'Appendix D: Troubleshooting Guide',
                'Appendix E: Resources and Further Reading',
            ],
        ],
    ];

    protected function getBookPath(): string
    {
        return config('book.path');
    }

    protected function getConverter(): MarkdownConverter
    {
        $config = [
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => '',
                'apply_id_to_heading' => true,
                'heading_class' => '',
                'fragment_prefix' => '',
                'insert' => 'before',
                'min_heading_level' => 1,
                'max_heading_level' => 6,
                'title' => 'Permalink',
                'symbol' => '#',
                'aria_hidden' => true,
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        return new MarkdownConverter($environment);
    }

    public function welcome()
    {
        return view('welcome', ['chapters' => $this->chapters]);
    }

    public function index()
    {
        return view('book.index', ['chapters' => $this->chapters]);
    }

    public function show(string $slug)
    {
        if (! isset($this->chapters[$slug])) {
            abort(404);
        }

        $chapter = $this->chapters[$slug];
        $filePath = $this->getBookPath() . '/' . $chapter['file'];

        if (! file_exists($filePath)) {
            abort(404, 'Chapter file not found');
        }

        $markdown = file_get_contents($filePath);
        // Remove duplicate H1 when the first line of the file is exactly "# {chapter title}"
        $titleHeading = '# ' . $chapter['title'];
        if (str_starts_with(trim($markdown), $titleHeading)) {
            $markdown = preg_replace(
                '/^\s*' . preg_quote($titleHeading, '/') . '\s*\r?\n/',
                '',
                $markdown,
                1
            );
        }
        $converter = $this->getConverter();
        $html = $converter->convert($markdown)->getContent();

        $keys = array_keys($this->chapters);
        $currentIndex = array_search($slug, $keys);
        $prevSlug = $currentIndex > 0 ? $keys[$currentIndex - 1] : null;
        $nextSlug = $currentIndex < count($keys) - 1 ? $keys[$currentIndex + 1] : null;

        return view('book.show', [
            'chapter' => $chapter,
            'content' => $html,
            'slug' => $slug,
            'chapters' => $this->chapters,
            'prevSlug' => $prevSlug,
            'nextSlug' => $nextSlug,
            'prevChapter' => $prevSlug ? $this->chapters[$prevSlug] : null,
            'nextChapter' => $nextSlug ? $this->chapters[$nextSlug] : null,
        ]);
    }
}
