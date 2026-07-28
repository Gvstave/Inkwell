<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows a reader to create an account', function () {
    $this->get(route('register'))->assertOk();

    $this->post(route('register'), [
        'name' => 'Ada Writer',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
});

it('shows only published posts on the public homepage', function () {
    $publishedPost = Post::factory()->published()->create(['title' => 'A public story']);
    Post::factory()->create(['title' => 'A private draft']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($publishedPost->title)
        ->assertDontSee('A private draft');
});

it('allows a published post to be read by its slug', function () {
    $post = Post::factory()->published()->create();

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee($post->title)
        ->assertSee($post->content);
});

it('allows only the author to preview a draft', function () {
    $draft = Post::factory()->create();
    $otherUser = User::factory()->create();

    $this->get(route('posts.show', $draft))->assertForbidden();
    $this->actingAs($otherUser)->get(route('posts.show', $draft))->assertForbidden();
    $this->actingAs($draft->user)->get(route('posts.show', $draft))->assertOk();
});

it('lets an author save a draft and publish it later', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('posts.store'), [
            'title' => 'My first essay',
            'excerpt' => 'A short introduction.',
            'content' => 'The complete essay.',
            'published' => '0',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Draft saved');

    $post = $user->posts()->sole();

    expect($post->published)->toBeFalse()
        ->and($post->slug)->toStartWith('my-first-essay-');

    $this->actingAs($user)
        ->put(route('posts.update', $post), [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'published' => '1',
        ])
        ->assertRedirect(route('dashboard'));

    expect($post->fresh()->published)->toBeTrue();
});

it('prevents authors from changing another authors post', function () {
    $post = Post::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->put(route('posts.update', $post), [
            'title' => 'Taken over',
            'content' => 'No.',
            'published' => '1',
        ])
        ->assertForbidden();

    expect($post->fresh()->title)->not->toBe('Taken over');
});

it('validates required post fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('posts.create'))
        ->post(route('posts.store'), [
            'title' => '',
            'content' => '',
            'published' => '0',
        ])
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors(['title', 'content']);
});
