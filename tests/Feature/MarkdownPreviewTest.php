<?php

use App\Actions\RenderMarkdownToHtml;
use App\Models\User;

test('guests cannot preview', function () {
    $this->post(route('markdown.preview'), ['text' => 'hello'])
        ->assertRedirect(route('login'));
});

test('it renders markdown the same way the page will', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('markdown.preview'), [
        'text' => "# Title\n\nSome **bold** text.\n\n| a | b |\n|---|---|\n| 1 | 2 |",
    ])
        ->assertOk()
        ->assertJson(fn ($json) => $json->where('html', fn (string $html): bool => str_contains($html, '<h1>Title</h1>')
            && str_contains($html, '<strong>bold</strong>')
            && str_contains($html, '<table>')));
});

test('it strips embedded html, as the stored render does', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('markdown.preview'), [
        'text' => "Safe text.\n\n<script>alert('x')</script>",
    ])
        ->assertOk()
        ->assertJson(fn ($json) => $json->where('html', fn (string $html): bool => str_contains($html, 'Safe text.')
            && ! str_contains($html, '<script>')));
});

test('nothing to preview comes back as nothing', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('markdown.preview'), ['text' => '   '])
        ->assertOk()
        ->assertJson(['html' => null]);

    $this->post(route('markdown.preview'), [])
        ->assertOk()
        ->assertJson(['html' => null]);
});

test('it refuses more text than anyone types', function () {
    $this->actingAs(User::factory()->create());

    $this->postJson(route('markdown.preview'), ['text' => str_repeat('a', 50_001)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('text');
});

test('a read-only account can preview, since it changes nothing', function () {
    $this->actingAs(User::factory()->readOnly()->create());

    $this->post(route('markdown.preview'), ['text' => '**bold**'])
        ->assertOk()
        ->assertJson(fn ($json) => $json->where('html', fn (string $html): bool => str_contains($html, '<strong>')));
});

test('the preview and the stored render agree', function () {
    $this->actingAs(User::factory()->create());

    $markdown = "## Heading\n\n- one\n- two\n\n`code` and [a link](https://example.test)";

    $response = $this->post(route('markdown.preview'), ['text' => $markdown]);

    expect($response->json('html'))
        ->toBe(app(RenderMarkdownToHtml::class)($markdown));
});
