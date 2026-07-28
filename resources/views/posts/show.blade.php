@extends('layouts.blog')

@section('content')
    <article class="mx-auto max-w-3xl px-6 py-16 sm:py-24">
        <a href="{{ route('home') }}" class="text-sm text-stone-500 hover:text-indigo-600">← All stories</a>

        <header class="mt-12 border-b border-stone-200 pb-10 dark:border-stone-800">
            @unless ($post->published)
                <span class="mb-5 inline-block rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-800">Draft preview</span>
            @endunless
            <h1 class="text-5xl font-semibold leading-tight tracking-tight sm:text-6xl">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="mt-6 text-xl leading-8 text-stone-500 dark:text-stone-400">{{ $post->excerpt }}</p>
            @endif
            <div class="mt-7 text-xs uppercase tracking-[0.18em] text-stone-400">
                {{ $post->user->name }} <span class="mx-2">·</span> {{ $post->created_at->format('F j, Y') }}
            </div>
        </header>

        <div class="mt-12 whitespace-pre-wrap text-lg leading-9 text-stone-700 dark:text-stone-300">{{ $post->content }}</div>
    </article>
@endsection
