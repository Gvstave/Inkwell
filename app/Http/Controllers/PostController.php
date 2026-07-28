<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::published()->with('user')->latest()->get();

        return view('posts.index', compact('posts'));
    }

    public function show(Post $post): View
    {
        $this->authorize('view', $post);

        $post->load('user');

        return view('posts.show', compact('post'));
    }

    public function dashboard(Request $request): View
    {
        $posts = $request->user()->posts()->latest('updated_at')->get();

        return view('dashboard', compact('posts'));
    }

    public function create(): View
    {
        return view('posts.create');
    }

    public function store(Request $request): RedirectResponse
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

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
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

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        $post->delete();

        return redirect()->route('dashboard')->with('status', 'Post deleted');
    }
}
