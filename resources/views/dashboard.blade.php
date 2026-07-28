@extends('layouts.blog')

@section('content')
    <div class="mx-auto max-w-3xl px-6 py-16 sm:py-20">
        <div class="flex items-end justify-between gap-6 border-b border-stone-200 pb-8 dark:border-stone-800">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-600">Your desk</p>
                <h1 class="mt-2 text-5xl font-semibold tracking-tight">My posts</h1>
            </div>
            <a href="{{ route('posts.create') }}" class="shrink-0 rounded-full bg-stone-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-600 dark:bg-stone-100 dark:text-stone-900">New post</a>
        </div>

        @if (session('status'))
            <p class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="mt-8 space-y-3">
            @forelse ($posts as $post)
                <article class="flex flex-col gap-4 rounded-xl border border-stone-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between dark:border-stone-800 dark:bg-stone-900">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <h2 class="truncate text-xl font-semibold">{{ $post->title }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider {{ $post->published ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950' : 'bg-stone-100 text-stone-500 dark:bg-stone-800' }}">
                                {{ $post->published ? 'Live' : 'Draft' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-stone-400">Updated {{ $post->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-4 text-sm">
                        <a href="{{ route('posts.show', $post) }}" class="text-stone-500 hover:text-stone-900 dark:hover:text-white">View</a>
                        <a href="{{ route('posts.edit', $post) }}" class="font-medium text-indigo-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-stone-300 px-6 py-16 text-center dark:border-stone-700">
                    <p class="text-2xl text-stone-500">Your desk is empty.</p>
                    <a href="{{ route('posts.create') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600">Write your first post →</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
