<?php

namespace App\Contracts;

use App\Concerns\HasItemLinks;

/**
 * A module that can appear at either end of an ItemLink.
 *
 * The first three methods are what a link table needs in order to render a
 * record from a module it knows nothing else about: what to call it, which
 * module it belongs to, and where to click through to.
 *
 * @see HasItemLinks for the implementation every module shares.
 */
interface Linkable
{
    public function linkLabel(): string;

    public function linkUrl(): string;

    public static function moduleLabel(): string;
}
