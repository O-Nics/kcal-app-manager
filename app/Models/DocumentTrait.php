<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait DocumentTrait
{
    public function documents(): MorphMany {
        return $this->morphMany(Document::class, 'documentable');
    }

}
