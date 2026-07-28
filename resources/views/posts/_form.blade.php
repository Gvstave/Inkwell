@php($post = $post ?? null)

<div class="mx-auto max-w-3xl px-6 py-12 sm:py-16">
    <form method="POST" action="{{ $post ? route('posts.update', $post) : route('posts.store') }}">
        @csrf
        @if ($post)
            @method('PUT')
        @endif

        <input
            type="text"
            name="title"
            aria-label="Title"
            placeholder="Title"
            value="{{ old('title', $post?->title) }}"
            autofocus
            class="w-full border-0 bg-transparent px-0 text-4xl font-semibold tracking-tight placeholder:text-stone-300 focus:ring-0 sm:text-5xl dark:placeholder:text-stone-700"
        >
        @error('title')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <input
            type="text"
            name="excerpt"
            aria-label="Excerpt"
            placeholder="A short excerpt (optional)"
            value="{{ old('excerpt', $post?->excerpt) }}"
            class="mt-5 w-full border-0 bg-transparent px-0 text-lg text-stone-500 placeholder:text-stone-300 focus:ring-0 dark:placeholder:text-stone-700"
        >
        @error('excerpt')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="my-8 h-px bg-stone-200 dark:bg-stone-800"></div>

        <textarea
            name="content"
            aria-label="Content"
            rows="18"
            placeholder="Start writing…"
            class="w-full resize-y border-0 bg-transparent px-0 text-lg leading-9 placeholder:text-stone-300 focus:ring-0 dark:placeholder:text-stone-700"
        >{{ old('content', $post?->content) }}</textarea>
        @error('content')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-stone-200 pt-6 dark:border-stone-800">
            <a href="{{ route('dashboard') }}" class="text-sm text-stone-500 hover:text-stone-900 dark:hover:text-white">Cancel</a>
            <div class="flex gap-3">
                <button type="submit" name="published" value="0" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium hover:bg-stone-100 dark:border-stone-700 dark:hover:bg-stone-800">Save as draft</button>
                <button type="submit" name="published" value="1" class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ $post?->published ? 'Update' : 'Publish' }}
                </button>
            </div>
        </div>
    </form>
</div>
