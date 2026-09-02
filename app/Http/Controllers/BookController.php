<?php

namespace App\Http\Controllers;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class BookController extends Controller
{
    protected array $chapters = [
        'preface' => [
            'file' => '01-PREFACE.md',
            'title' => 'Preface',
            'part' => 'Front Matter',
        ],
        'strategy-and-workflow-selection' => [
            'file' => '02-STRATEGY-AND-WORKFLOW-SELECTION.md',
            'title' => 'Strategy & Workflow Selection',
            'part' => 'Part I — Strategy',
        ],
        'audit' => [
            'file' => '03-AUDIT.md',
            'title' => 'Audit — Current Workflow Mapping',
            'part' => 'Part II — A.G.E.N.T.',
        ],
        'gauge' => [
            'file' => '04-GAUGE.md',
            'title' => 'Gauge — Workflow Assessment',
            'part' => 'Part II — A.G.E.N.T.',
        ],
        'engineer' => [
            'file' => '05-ENGINEER.md',
            'title' => 'Engineer — Agent-First Redesign',
            'part' => 'Part II — A.G.E.N.T.',
        ],
        'navigate' => [
            'file' => '06-NAVIGATE.md',
            'title' => 'Navigate — Human-Agent Collaboration',
            'part' => 'Part II — A.G.E.N.T.',
        ],
        'track' => [
            'file' => '07-TRACK.md',
            'title' => 'Track — Value Measurement',
            'part' => 'Part II — A.G.E.N.T.',
        ],
        'implementation' => [
            'file' => '08-IMPLEMENTATION.md',
            'title' => 'Implementation — Pilot Charter',
            'part' => 'Part III — Execution',
        ],
        'appendix' => [
            'file' => '09-APPENDIX.md',
            'title' => 'Appendix — Radical Workflow Redesign',
            'part' => 'Appendix',
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
        $filePath = $this->getBookPath().'/'.$chapter['file'];

        if (! file_exists($filePath)) {
            abort(404, 'Chapter file not found');
        }

        $markdown = file_get_contents($filePath);
        $titleHeading = '# '.$chapter['title'];
        if (str_starts_with(trim($markdown), $titleHeading)) {
            $markdown = preg_replace(
                '/^\s*'.preg_quote($titleHeading, '/').'\s*\r?\n/',
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
