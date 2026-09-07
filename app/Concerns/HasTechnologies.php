<?php

namespace App\Concerns;

use App\Models\TechnologyUsage;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Marks a record as something that can be built from technologies.
 */
trait HasTechnologies
{
    /**
     * @return MorphMany<TechnologyUsage, $this>
     */
    public function technologyUsages(): MorphMany
    {
        return $this->morphMany(TechnologyUsage::class, 'usable');
    }
}
