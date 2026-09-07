<?php

namespace App\Concerns;

use App\Models\ItemLink;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * The shared half of App\Contracts\Linkable: the two morph relations every
 * linkable module resolves the same way. The labelling methods stay on each
 * model, since only the model knows what it is called.
 */
trait HasItemLinks
{
    /**
     * @return MorphMany<ItemLink, $this>
     */
    public function outgoingItemLinks(): MorphMany
    {
        return $this->morphMany(ItemLink::class, 'source');
    }

    /**
     * @return MorphMany<ItemLink, $this>
     */
    public function incomingItemLinks(): MorphMany
    {
        return $this->morphMany(ItemLink::class, 'target');
    }
}
