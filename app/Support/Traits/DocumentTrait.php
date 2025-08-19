<?php

namespace App\Support\Traits;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait DocumentTrait
{
    public function documents(): MorphMany {
        return $this->morphMany(Document::class, 'documentable');
    }

}
