<?php

namespace App\Contracts;

/**
 * An enum that knows how to name itself for a person.
 *
 * Every status enum in the app already had a label(); this makes that promise
 * something the type system can rely on, so anything counting or listing cases
 * can ask for the name without knowing which enum it holds.
 */
interface Labelled
{
    public function label(): string;
}
