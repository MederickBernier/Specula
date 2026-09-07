<?php

use App\Enums\ConfidenceLevel;
use App\Enums\PrototypeStatus;
use App\Models\Prototype;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function prototypePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Queue the reporting job',
        'status' => PrototypeStatus::Planned->value,
        'hypothesis' => 'Moving it **off** the request keeps p95 under a second.',
        'test_approach' => null,
        'result' => null,
        'abandoned_reason' => null,
        'confidence_level' => null,
        'is_reusable' => null,
        'reusability_note' => null,
        'repo_reference' => null,
        'date_started' => '2026-09-01',
    ], $overrides);
}

test('guests cannot reach the prototypes', function () {
    auth()->logout();

    $this->get(route('prototypes.index'))->assertRedirect(route('login'));
    $this->get(route('prototypes.create'))->assertRedirect(route('login'));
    $this->post(route('prototypes.store'), [])->assertRedirect(route('login'));
});

test('the index lists prototypes newest started first', function () {
    Prototype::factory()->create(['title' => 'Older', 'date_started' => '2026-01-01']);
    Prototype::factory()->create(['title' => 'Newer', 'date_started' => '2026-06-01']);

    $this->get(route('prototypes.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('prototypes/index')
            ->has('prototypes', 2)
            ->where('prototypes.0.title', 'Newer')
            ->where('prototypes.1.title', 'Older')
            ->has('statuses', 4)
            ->has('confidenceLevels', 3));
});

test('it stores a planned prototype without a result', function () {
    $this->post(route('prototypes.store'), prototypePayload())
        ->assertRedirect(route('prototypes.show', Prototype::first()));

    $prototype = Prototype::first();

    expect($prototype->status)->toBe(PrototypeStatus::Planned)
        ->and($prototype->result)->toBeNull()
        ->and($prototype->is_reusable)->toBeNull()
        ->and($prototype->date_completed)->toBeNull();
});

test('a completed prototype needs a result and a confidence level', function () {
    $this->post(route('prototypes.store'), prototypePayload([
        'status' => PrototypeStatus::Completed->value,
    ]))->assertSessionHasErrors(['result', 'confidence_level']);

    expect(Prototype::count())->toBe(0);
});

test('an abandoned prototype needs a reason', function () {
    $this->post(route('prototypes.store'), prototypePayload([
        'status' => PrototypeStatus::Abandoned->value,
    ]))->assertSessionHasErrors('abandoned_reason');

    expect(Prototype::count())->toBe(0);
});

test('an abandoned prototype does not need a result', function () {
    $this->post(route('prototypes.store'), prototypePayload([
        'status' => PrototypeStatus::Abandoned->value,
        'abandoned_reason' => 'Deprioritised before it ran.',
    ]))->assertSessionHasNoErrors();

    expect(Prototype::first()->result)->toBeNull();
});

test('it stamps the completion date when a prototype finishes', function () {
    $prototype = Prototype::factory()->inProgress()->create();

    expect($prototype->date_completed)->toBeNull();

    $this->put(route('prototypes.update', $prototype), prototypePayload([
        'status' => PrototypeStatus::Completed->value,
        'result' => 'p95 dropped to 600ms.',
        'confidence_level' => ConfidenceLevel::High->value,
    ]))->assertRedirect(route('prototypes.show', $prototype));

    expect($prototype->refresh()->date_completed)->not->toBeNull()
        ->and($prototype->confidence_level)->toBe(ConfidenceLevel::High);
});

test('it stamps the completion date when a prototype is abandoned', function () {
    $prototype = Prototype::factory()->abandoned()->create();

    expect($prototype->date_completed)->not->toBeNull();
});

test('it clears the completion date when a prototype restarts', function () {
    $prototype = Prototype::factory()->completed()->create();

    expect($prototype->date_completed)->not->toBeNull();

    $this->put(route('prototypes.update', $prototype), prototypePayload([
        'status' => PrototypeStatus::InProgress->value,
    ]));

    expect($prototype->refresh()->date_completed)->toBeNull();
});

test('it records reusability and a repo reference', function () {
    $this->post(route('prototypes.store'), prototypePayload([
        'status' => PrototypeStatus::Completed->value,
        'result' => 'Worked.',
        'confidence_level' => ConfidenceLevel::Medium->value,
        'is_reusable' => true,
        'reusability_note' => 'The queue wiring lifts straight out.',
        'repo_reference' => 'spike/queued-reports',
    ]))->assertSessionHasNoErrors();

    $prototype = Prototype::first();

    expect($prototype->is_reusable)->toBeTrue()
        ->and($prototype->repo_reference)->toBe('spike/queued-reports');
});

test('it deletes a prototype', function () {
    $prototype = Prototype::factory()->create();

    $this->delete(route('prototypes.destroy', $prototype))
        ->assertRedirect(route('prototypes.index'));

    expect(Prototype::count())->toBe(0);
});

test('the show page renders markdown and strips raw html', function () {
    $prototype = Prototype::factory()->create([
        'hypothesis' => "Moving it **off** the request helps.\n\n<script>alert('x')</script>",
        'result' => null,
    ]);

    $this->get(route('prototypes.show', $prototype))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('prototypes/show')
            ->where('html.hypothesis', fn (string $html) => str_contains($html, '<strong>off</strong>')
                && ! str_contains($html, '<script>'))
            ->where('html.result', null));
});
