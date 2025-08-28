<?php
namespace App\Models\Test;

use App\Models\Documentable;
use App\Support\Traits\DocumentTrait;
use Illuminate\Database\Eloquent\Model;

class TestDocumentable extends Model implements Documentable
{
    use DocumentTrait;
    protected $table = 'test_documentables';
    protected $guarded = [];

    public function storagePath(): string
    {
        // Chaque instance aura son dossier
        return "test_docs/{$this->getKey()}";
    }

}
