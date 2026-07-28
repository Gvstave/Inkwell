# Rebuilding Inkwell in Laravel + Blade

Your app is a minimal blog called **Inkwell**: sign up/sign in, write posts (title, excerpt, content), save as draft or publish, a public homepage of published posts, individual post pages by slug, and a dashboard where authors manage their own posts. It's currently React + Supabase. Below is the same app rebuilt with classic Laravel + Blade — no React, no JS framework, just PHP templates. This assumes zero Laravel/PHP experience, so it explains *why*, not just *what*.

---

## 0. The mental model shift

| Inkwell (React + Supabase) | Laravel equivalent |
|---|---|
| Supabase Auth (email/password, Google OAuth) | Laravel's built-in auth, scaffolded by **Breeze** |
| `profiles` table (display_name, avatar_url) | The built-in `users` table (`name` column = display_name) |
| `posts` table + Row Level Security policies | `posts` table (migration) + a **Policy** class that encodes the same rules in PHP |
| React Query fetching from Supabase client | A **Controller** method querying the database with **Eloquent** |
| `.tsx` route files | **Routes** (`routes/web.php`) + **Blade view** files (`.blade.php`) |
| `PostEditor.tsx` component | One shared Blade form partial reused by "create" and "edit" pages |
| `slugify()` in `lib/slug.ts` | A PHP helper, same logic, triggered when a Post is created |

Laravel is a full-stack PHP framework: it handles routing, database access (via an ORM called Eloquent), and rendering HTML (via a templating language called Blade) all in one place — you won't need a separate backend API.

---

## 1. Install the tools

You need:
- **PHP 8.2+** — check with `php -v`
- **Composer** (PHP's package manager) — [getcomposer.org](https://getcomposer.org)
- **Node.js + npm** — still needed, only for compiling Tailwind CSS, not for a JS framework
- A database — start with **SQLite**, it's a single file, zero setup, perfect for learning

Install the Laravel installer once:
```bash
composer global require laravel/installer
```

## 2. Create the project

```bash
laravel new inkwell
cd inkwell
```
It'll ask a few setup questions — pick **Blade** as the starter kit if offered (or skip and we'll add auth manually in step 4).

Start it running to confirm it works:
```bash
php artisan serve
```
Visit `http://localhost:8000` — you should see the default Laravel welcome page.

## 3. Set up the database

Open `.env` in the project root and set:
```
DB_CONNECTION=sqlite
```
Comment out or remove the other `DB_*` lines. Then:
```bash
touch database/database.sqlite
php artisan migrate
```
This runs Laravel's default migrations (creates the `users` table, among others).

## 4. Add authentication (replaces Supabase Auth)

Laravel Breeze scaffolds login, registration, password reset, and profile editing for you — Blade views included.

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run dev
php artisan migrate
```

That's it — you now have `/login`, `/register`, `/dashboard` (protected), and logout, all working, styled with Tailwind. This replaces Supabase's `signUp` / `signInWithPassword` / the `profiles` table entirely. The `users` table's `name` column plays the role of `display_name`. (Google sign-in is a later add-on via **Laravel Socialite** — skip it for now and get email/password working first.)

## 5. Create the Post model + migration

```bash
php artisan make:model Post -m
```
This creates `app/Models/Post.php` and a migration file in `database/migrations/`. Open the new migration file and replace the `up()` method:

```php
public function up(): void
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->string('slug')->unique();
        $table->string('excerpt')->nullable();
        $table->longText('content');
        $table->boolean('published')->default(false);
        $table->timestamps();
    });
}
```
This mirrors your Supabase `posts` table (`author_id` → `user_id`, everything else the same).

Run it:
```bash
php artisan migrate
```

## 6. The Post model (relationships + slug logic)

Replace the contents of `app/Models/Post.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'published'];

    protected $casts = [
        'published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = static::generateSlug($post->title);
            }
        });
    }

    public static function generateSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        return $base . '-' . Str::lower(Str::random(5));
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }
}
```
This is a direct port of `slugify()` from your `lib/slug.ts` — lowercase, hyphenated, random suffix for uniqueness.

Now open `app/Models/User.php` and add the inverse relationship inside the class:
```php
public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Post::class);
}
```

## 7. The Policy (replaces Row Level Security)

Your Supabase migration had RLS policies like "authors can update their own posts." In Laravel, that logic lives in a **Policy** class instead of the database.

```bash
php artisan make:policy PostPolicy --model=Post
```

Replace `app/Policies/PostPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(?User $user, Post $post): bool
    {
        return $post->published || ($user && $user->id === $post->user_id);
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```
Laravel auto-discovers this policy because it's named `PostPolicy` for the `Post` model — no manual registration needed.

## 8. Routes

Open `routes/web.php` and replace its contents:

```php
<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index'])->name('home');
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PostController::class, 'dashboard'])->name('dashboard');
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
```
Note `{post:slug}` on the public show route — that tells Laravel "look this Post up by its `slug` column," matching Supabase's `.eq('slug', slug)` query. The authenticated routes bind by `id` instead (the default), which is how the dashboard's edit/delete links will work.

## 9. The Controller

```bash
php artisan make:controller PostController
```
Replace `app/Http/Controllers/PostController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::published()->with('user')->latest()->get();
        return view('posts.index', compact('posts'));
    }

    public function show(Post $post)
    {
        $this->authorize('view', $post);
        return view('posts.show', compact('post'));
    }

    public function dashboard(Request $request)
    {
        $posts = $request->user()->posts()->latest('updated_at')->get();
        return view('dashboard', compact('posts'));
    }

    public function create()
    {
        return view('posts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $post = $request->user()->posts()->create([
            ...$data,
            'published' => $request->input('published') === '1',
        ]);

        return redirect()->route('dashboard')
            ->with('status', $post->published ? 'Published' : 'Draft saved');
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $post->update([
            ...$data,
            'published' => $request->input('published') === '1',
        ]);

        return redirect()->route('dashboard')->with('status', 'Saved');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return redirect()->route('dashboard')->with('status', 'Post deleted');
    }
}
```
`$this->authorize(...)` is what enforces the Policy from step 7 — if it fails, Laravel automatically returns a 403 error page. This is your RLS, moved into PHP.

## 10. The views

Blade views live in `resources/views/`. Breeze already gave you `resources/views/layouts/app.blade.php` with a nav bar — this walkthrough builds on it.

**`resources/views/posts/index.blade.php`** (homepage — public feed):
```blade
<x-app-layout>
    <div class="max-w-2xl mx-auto py-16 px-6">
        <h1 class="text-5xl font-serif">A quiet place<br><span class="italic text-indigo-600">to write.</span></h1>
        <p class="mt-6 text-gray-500">No ads, no algorithm — just words.</p>

        <div class="mt-12 space-y-10">
            @forelse ($posts as $post)
                <a href="{{ route('posts.show', $post) }}" class="block group">
                    <h2 class="text-3xl group-hover:text-indigo-600">{{ $post->title }}</h2>
                    @if ($post->excerpt)
                        <p class="mt-2 text-gray-500 line-clamp-2">{{ $post->excerpt }}</p>
                    @endif
                    <div class="mt-3 text-xs uppercase tracking-wider text-gray-400">
                        {{ $post->user->name }} &middot; {{ $post->created_at->format('F j, Y') }}
                    </div>
                </a>
            @empty
                <p class="italic text-gray-500">Nothing published yet. Be the first.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
```

**`resources/views/posts/show.blade.php`** (single post):
```blade
<x-app-layout>
    <div class="max-w-2xl mx-auto py-16 px-6">
        <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-indigo-600">&larr; Back</a>
        <article class="mt-8">
            <h1 class="text-5xl leading-tight">{{ $post->title }}</h1>
            <div class="mt-4 text-sm uppercase tracking-wider text-gray-400">
                {{ $post->user->name }} &middot; {{ $post->created_at->format('F j, Y') }}
            </div>
            <div class="mt-10 whitespace-pre-wrap leading-relaxed">{{ $post->content }}</div>
        </article>
    </div>
</x-app-layout>
```

**`resources/views/dashboard.blade.php`** (replace Breeze's default with your post list):
```blade
<x-app-layout>
    <div class="max-w-2xl mx-auto py-16 px-6">
        <div class="flex items-baseline justify-between">
            <h1 class="text-4xl">My posts</h1>
            <a href="{{ route('posts.create') }}" class="rounded-md bg-indigo-600 text-white px-4 py-2 text-sm">New post</a>
        </div>

        @if (session('status'))
            <p class="mt-4 text-sm text-green-600">{{ session('status') }}</p>
        @endif

        <div class="mt-10 space-y-4">
            @forelse ($posts as $post)
                <div class="flex items-center justify-between rounded-lg border px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="truncate font-medium">{{ $post->title }}</h3>
                            <span class="text-[10px] uppercase rounded-full px-2 py-0.5 {{ $post->published ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $post->published ? 'Live' : 'Draft' }}
                            </span>
                        </div>
                    </div>
                    <div class="ml-4 flex gap-3 text-sm shrink-0">
                        @if ($post->published)
                            <a href="{{ route('posts.show', $post) }}" class="text-gray-500 hover:text-gray-800">View</a>
                        @endif
                        <a href="{{ route('posts.edit', $post) }}" class="text-indigo-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed p-12 text-center">
                    <p class="italic text-gray-500">No posts yet.</p>
                    <a href="{{ route('posts.create') }}" class="mt-4 inline-block text-indigo-600 hover:underline">Write your first post &rarr;</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
```

**`resources/views/posts/_form.blade.php`** (shared editor — used by both create & edit, mirrors `PostEditor.tsx`):
```blade
@php $post = $post ?? null; @endphp
<div class="max-w-2xl mx-auto py-16 px-6">
    <form method="POST" action="{{ $post ? route('posts.update', $post) : route('posts.store') }}">
        @csrf
        @if ($post) @method('PUT') @endif

        <input type="text" name="title" placeholder="Title" value="{{ old('title', $post->title ?? '') }}"
               class="w-full text-4xl font-serif border-0 focus:ring-0 px-0 placeholder:text-gray-300">
        @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <input type="text" name="excerpt" placeholder="Short excerpt (optional)" value="{{ old('excerpt', $post->excerpt ?? '') }}"
               class="mt-4 w-full text-lg text-gray-500 border-0 focus:ring-0 px-0 placeholder:text-gray-300">

        <div class="my-6 h-px bg-gray-200"></div>

        <textarea name="content" rows="20" placeholder="Start writing…"
                  class="w-full border-0 focus:ring-0 px-0 leading-relaxed">{{ old('content', $post->content ?? '') }}</textarea>
        @error('content') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <div class="mt-8 flex justify-end gap-3">
            <button type="submit" name="published" value="0" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
                Save as draft
            </button>
            <button type="submit" name="published" value="1" class="rounded-md bg-indigo-600 text-white px-4 py-2 text-sm hover:opacity-90">
                {{ $post?->published ? 'Update' : 'Publish' }}
            </button>
        </div>
    </form>
</div>
```
The two submit buttons trick — `name="published" value="0"` vs `value="1"` — is how a plain HTML form does what your React state (`save(published)`) did with two click handlers. Whichever button is clicked, its `value` is the one sent.

**`resources/views/posts/create.blade.php`**:
```blade
<x-app-layout>
    @include('posts._form')
</x-app-layout>
```

**`resources/views/posts/edit.blade.php`**:
```blade
<x-app-layout>
    @include('posts._form', ['post' => $post])
</x-app-layout>
```

## 11. Run it

```bash
php artisan serve
npm run dev   # in a second terminal, compiles Tailwind
```
Visit `http://localhost:8000`, register an account, write a post, save as draft, publish it, view it on the homepage.

## 12. What's deliberately left out (add later once this works)

- **Google sign-in** → add via [Laravel Socialite](https://laravel.com/docs/socialite) once email/password auth feels comfortable.
- **Avatars** → add an `avatar_url` column to `users` via a new migration, plus a file upload field.
- **Markdown rendering / rich text editor** for `content`, instead of a plain textarea.
- **Search or pagination** on the homepage once you have more than a handful of posts (`Post::published()->paginate(10)`).

## 13. How to keep learning

- `php artisan tinker` — a REPL to poke at your models (`Post::first()`, `Post::create([...])`) without writing routes.
- `php artisan route:list` — see every route you've defined.
- Laravel's own docs are unusually good for beginners: https://laravel.com/docs — read the "Routing," "Eloquent," "Blade," and "Authorization" chapters, in that order, and you'll have covered everything used above.
