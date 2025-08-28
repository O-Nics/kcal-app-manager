<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;


/**
 * @property int $id
 * @property string $file_name
 * @property string $disk
 * @property string $token
 * @property string $created_at
 * @property string $updated_at
 * @property-read Model $documentable
 * @property int|null $width
 * @property int|null $height
 * @property int|null $number
 * @property string|null $thumbs
 * @property string|null $collection
 * @property string|null $private
 * @property string $name
 *
 */
class Document extends Model
{

    protected $fillable = [
        'file_name',
        'disk',
        'width',
        'height',
        'number',
        'thumbs',
        'collection',
        'private',
        'name',
    ];
    public function documentable(): MorphTo {
        return $this->morphTo();
    }
}
