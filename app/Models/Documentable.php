<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Documentable
{
    public function storagePath(): string;

    public function documents(): MorphMany;

}
