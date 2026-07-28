@extends('layouts.blog')

@section('content')
    <div class="mx-auto max-w-3xl px-6 py-20 sm:py-28">
        <section class="border-b border-stone-200 pb-16 dark:border-stone-800">
            <p class="mb-5 text-xs font-semibold uppercase tracking-[0.24em] text-indigo-600">Stories worth sitting with</p>
            <h1 class="text-5xl font-semibold leading-[1.05] tracking-tight sm:text-7xl">
                A quiet place<br><span class="text-indigo-600">to write.</span>
            </h1>
            <p class="mt-7 max-w-lg text-lg leading-8 text-stone-500 dark:text-stone-400">No ads, no algorithm—just words from people with something to say.</p>
        </section>

        <div class="divide-y divide-stone-200 dark:divide-stone-800">
            @forelse ($posts as $post)
                <article class="py-10">
                    <a href="{{ route('posts.show', $post) }}" class="group block">
                        <div class="mb-4 text-xs uppercase tracking-[0.18em] text-stone-400">
                            {{ $post->user->name }} <span class="mx-2">·</span> {{ $post->created_at->format('F j, Y') }}
                        </div>
                        <h2 class="text-3xl font-semibold tracking-tight transition group-hover:text-indigo-600 sm:text-4xl">{{ $post->title }}</h2>
                        @if ($post->excerpt)
                            <p class="mt-3 text-base leading-7 text-stone-500 dark:text-stone-400">{{ $post->excerpt }}</p>
                        @endif
                        <span class="mt-5 inline-block text-sm font-medium text-indigo-600">Read story →</span>
                    </a>
                </article>
            @empty
                <div class="py-16 text-center">
                    <p class="text-2xl text-stone-500">Nothing published yet.</p>
                    @auth
                        <a href="{{ route('posts.create') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600">Write the first story →</a>
                    @endauth
                </div>
            @endforelse
        </div>
    </div>
@endsection
