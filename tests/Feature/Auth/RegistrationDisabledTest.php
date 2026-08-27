<?php

use Laravel\Fortify\Features;

test('the registration route is not registered', function () {
    $this->get('/register')->assertNotFound();
});

test('registration is not an enabled fortify feature', function () {
    expect(config('fortify.features'))
        ->not->toContain(Features::registration());
});
