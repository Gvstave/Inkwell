<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-50 font-sans text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
        <header class="border-b border-stone-200/80 dark:border-stone-800">
            <nav class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                <a href="{{ route('home') }}" class="text-2xl font-semibold tracking-tight">Inkwell.</a>

                <div class="flex items-center gap-5 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-stone-600 hover:text-indigo-600 dark:text-stone-300">My posts</a>
                        <a href="{{ route('posts.create') }}" class="rounded-full bg-stone-900 px-4 py-2 text-white hover:bg-indigo-600 dark:bg-stone-100 dark:text-stone-900">Write</a>
                    @else
                        <a href="{{ route('login') }}" class="text-stone-600 hover:text-indigo-600 dark:text-stone-300">Sign in</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-stone-900 px-4 py-2 text-white hover:bg-indigo-600 dark:bg-stone-100 dark:text-stone-900">Start writing</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        @fluxScripts
    </body>
</html>
