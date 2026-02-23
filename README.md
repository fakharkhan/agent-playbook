# AI for Artisans — Book Site

Laravel web application for **AI for Artisans: Mastering the Laravel AI SDK** by Fakhar Zaman Khan.

Live site: [ai-for-artisans.fakharkhan.com](https://ai-for-artisans.fakharkhan.com)

## What's included

- **Laravel app** — Landing page, table of contents, and chapter reader with Markdown rendering
- **Book content** — All chapters in `book/` (Markdown), rendered on the fly. Path is configurable in `config/book.php`.

## Requirements

- PHP 8.4+
- Composer
- Node.js & npm (for frontend assets)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
php artisan serve
```

Visit `http://localhost:8000`. No database required.

## Deployment

- Set `APP_URL` to your domain (e.g. `https://ai-for-artisans.fakharkhan.com`)
- Run `php artisan config:cache` and `php artisan view:cache` in production
- Point your web server (Nginx/Apache) at the `public/` directory

## License

The Laravel framework is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).  
Book content © Fakhar Zaman Khan. This is an independent, community-authored guide and is not affiliated with Laravel LLC or Taylor Otwell.
