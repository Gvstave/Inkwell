#Inkwell

A minimal blogging space for thoughtful writers. No ads, no algorithm — just words, paragraphs, and the occasional em dash.

This is a Laravel rebuild of the original Inkwell app (previously React + Supabase).

##Features
*Authentication — register and log in with email/password
*Write posts — title, optional short excerpt, and body content
*Drafts & publishing — save a post as a draft or publish it, and update either state later
*Public feed — homepage lists all published posts, newest first, with author name and date
*Individual post pages — each published post gets its own page at /posts/{slug}
*Author dashboard — logged-in users see a list of their own posts (draft or live), with edit and delete actions
*Ownership rules — only a post's author can edit or delete it; drafts are only visible to their author, published posts are visible to everyone
*Auto-generated slugs — URLs are generated automatically from the post title on creation
##Tech Stack

Backend / Framework

*Laravel (v13) — PHP framework handling routing, database access, and rendering
PHP 8.2+

##Frontend / Templating

*Blade — Laravel's server-side templating language
*Livewire (with Volt single-file components) — adds interactivity to Blade views without a separate JS framework
**Tailwind CSS — utility-first styling
Vite — asset bundling for CSS/JS

##Authentication

Laravel's built-in authentication (registration enabled; email verification, 2FA, passkeys, and password confirmation not enabled)

##Database

SQLite (single-file, no separate database server required for local development)

##Authorization

Laravel Policies (PostPolicy) — encode who can view, edit, or delete a post, replacing what was previously handled by Postgres Row Level Security

##Data Model

###users

name, email, password (standard Laravel auth fields — name doubles as the display name)

###posts

title, slug (unique, auto-generated), excerpt (nullable), content, published (boolean), user_id (author, foreign key)

##Local Development
bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

##Run the app (two terminals):

bash
php artisan serve
npm run dev

Visit http://localhost:8000.

##Roadmap / Not Yet Implemented
Google / social sign-in
User avatars
Rich text or Markdown rendering for post content
Search and pagination on the homepage
