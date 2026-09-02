# The A.G.E.N.T. Playbook

Publishable book site for **The A.G.E.N.T. Playbook** — Fakhar Khan's applied agentic AI workflow guide from the Harvard Data Science Review intensive (April 2026).

**Live URL (when deployed):** https://agent-playbook.fakharkhan.com

## Structure

Markdown chapters live in `book/`. Chapter routing is defined in `app/Http/Controllers/BookController.php`.

| Slug | Chapter |
|------|---------|
| `preface` | Preface |
| `strategy-and-workflow-selection` | Strategy & Workflow Selection |
| `audit` | Audit |
| `gauge` | Gauge |
| `engineer` | Engineer |
| `navigate` | Navigate |
| `track` | Track |
| `implementation` | Implementation (pilot charter) |
| `appendix` | Appendix |

## Source material

Content derived from the NXGL-HDSR-COURSE training folder:

- `Your Agentic AI Framework.docx` (final playbook)
- `docs/next-steps-toward-implementation-section-6.md`
- `docs/your-agentic-ai-strategy-section-4-3-and-4-4.md`
- `docs/how-this-course-connects-to-professional-goals.md`
- `docs/lesson-chat-354006-radical-workflow-redesign-summary.md`

## Local development

```bash
composer install
npm install && npm run build
php artisan serve
```

Or use Laravel Herd: site served at `agent-playbook.test` when linked.

## Deploy

Same pattern as `ai-for-artisans` and `book-beyond-code`:

1. Push to GitHub (`fakharkhan/agent-playbook`)
2. Forge site on subdomain `agent-playbook.fakharkhan.com`
3. `composer install --no-dev`, `npm run build`, `php artisan config:cache && php artisan view:cache`

No database required.

## Linked from

- [fakharkhan.com/about](https://fakharkhan.com/about) — Books section
- [fakharkhan.com](https://fakharkhan.com) footer
