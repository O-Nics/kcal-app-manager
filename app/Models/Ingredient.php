<?php

namespace App\Models;

use App\Support\Traits\DocumentTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Ingredient
 *
 * @property int $id
 * @property bool $verified
 * @property string|null $created_by
 * @property int $ingredient_type_id
 * @property string $name
 * @property string $default_unit
 * @property int $reference_value
 * @property int $kcal_for_100
 * @property string|null $barcode
 * @property string|null $conservation_conditions
 * @property int|null $image_id
 * @property bool $is_ai
 * @property string|null $product_quantity_unit
 * @property float|null $product_quantity
 * @property float $fat
 * @property float $saturated_fat
 * @property float|null $mono_saturated_fat
 * @property float|null $poly_in_saturated_fat
 * @property float $glucoside
 * @property float $sugar
 * @property float $fiber
 * @property float $protein
 * @property float|null $salt
 * @property float|null $sodium
 * @property float|null $iron
 * @property float|null $copper
 * @property float|null $vitamin_a
 * @property float|null $vitamin_c
 * @property float|null $vitamin_d
 * @property float|null $vitamin_b1
 * @property float|null $vitamin_b2
 * @property float|null $vitamin_b3
 * @property float|null $vitamin_b5
 * @property float|null $vitamin_b6
 * @property float|null $vitamin_b7
 * @property float|null $vitamin_b9
 * @property float|null $vitamin_b11
 * @property float|null $vitamin_b12
 * @property float|null $vitamin_e
 * @property float|null $vitamin_k
 * @property float|null $calcium
 * @property float|null $fer
 * @property float|null $arsenic
 * @property float|null $bore
 * @property float|null $biotin
 * @property float|null $choline
 * @property float|null $cholesterol
 * @property float|null $chlorure
 * @property float|null $chrome
 * @property float|null $cobalt
 * @property float|null $polyols
 * @property float|null $cuivre
 * @property float|null $fluorure
 * @property float|null $fluor
 * @property float|null $iode
 * @property float|null $magnesium
 * @property float|null $manganese
 * @property float|null $lactose
 * @property float|null $maltose
 * @property float|null $glucose
 * @property float|null $fructose
 * @property float|null $molybdene
 * @property float|null $phosphorus
 * @property float|null $potassium
 * @property float|null $rubidium
 * @property float|null $selenium
 * @property float|null $silicone
 * @property float|null $soufre
 * @property float|null $pantothenic_acid
 * @property float|null $etain
 * @property float|null $vanadium
 * @property float|null $zinc
 * @property float|null $eau
 * @property float|null $alcool
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read \App\Models\Customer|null $creator
 * @property-read \App\Models\IngredientType $type
 * @property-read \App\Models\Document|null $image
 * @property-read Collection|\App\Models\Brand[] $brands
 */
class Ingredient extends Model implements Documentable
{

    use HasFactory, DocumentTrait, SoftDeletes;

    protected $fillable = [
        'verified', 'created_by', 'ingredient_type_id', 'name', 'default_unit', 'reference_value',
        'kcal_for_100', 'barcode', 'conservation_conditions', 'image_id', 'is_ai',
        'product_quantity_unit', 'product_quantity',
        'fat', 'saturated_fat', 'mono_saturated_fat', 'poly_in_saturated_fat',
        'glucoside', 'sugar', 'fiber', 'protein', 'salt', 'sodium', 'iron', 'copper',
        'vitamin_a','vitamin_c','vitamin_d','vitamin_b1','vitamin_b2','vitamin_b3','vitamin_b5','vitamin_b6','vitamin_b7','vitamin_b9','vitamin_b11','vitamin_b12','vitamin_e','vitamin_k',
        'calcium','fer','arsenic','bore','biotin','choline','cholesterol','chlorure','chrome','cobalt','polyols','cuivre','fluorure','fluor','iode','magnesium','manganese','lactose','maltose','glucose','fructose','molybdene','phosphorus','potassium','rubidium','selenium','silicone','soufre','pantothenic_acid','etain','vanadium','zinc','eau','alcool',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'is_ai' => 'boolean',
        'product_quantity' => 'float',
        'fat' => 'float',
        'saturated_fat' => 'float',
        'mono_saturated_fat' => 'float',
        'poly_in_saturated_fat' => 'float',
        'glucoside' => 'float',
        'sugar' => 'float',
        'fiber' => 'float',
        'protein' => 'float',
        'salt' => 'float',
        'sodium' => 'float',
        'iron' => 'float',
        'copper' => 'float',
        'vitamin_a' => 'float',
        'vitamin_c' => 'float',
        'vitamin_d' => 'float',
        'vitamin_b1' => 'float',
        'vitamin_b2' => 'float',
        'vitamin_b3' => 'float',
        'vitamin_b5' => 'float',
        'vitamin_b6' => 'float',
        'vitamin_b7' => 'float',
        'vitamin_b9' => 'float',
        'vitamin_b11' => 'float',
        'vitamin_b12' => 'float',
        'vitamin_e' => 'float',
        'vitamin_k' => 'float',
        'calcium' => 'float',
        'fer' => 'float',
        'arsenic' => 'float',
        'bore' => 'float',
        'biotin' => 'float',
        'choline' => 'float',
        'cholesterol' => 'float',
        'chlorure' => 'float',
        'chrome' => 'float',
        'cobalt' => 'float',
        'polyols' => 'float',
        'cuivre' => 'float',
        'fluorure' => 'float',
        'fluor' => 'float',
        'iode' => 'float',
        'magnesium' => 'float',
        'manganese' => 'float',
        'lactose' => 'float',
        'maltose' => 'float',
        'glucose' => 'float',
        'fructose' => 'float',
        'molybdene' => 'float',
        'phosphorus' => 'float',
        'potassium' => 'float',
        'rubidium' => 'float',
        'selenium' => 'float',
        'silicone' => 'float',
        'soufre' => 'float',
        'pantothenic_acid' => 'float',
        'etain' => 'float',
        'vanadium' => 'float',
        'zinc' => 'float',
        'eau' => 'float',
        'alcool' => 'float',
    ];

    // Relationships
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'created_by');
    }

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IngredientType::class, 'ingredient_type_id');
    }

    public function image(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Document::class, 'image_id');
    }

    public function brands(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_ingredients')->withTimestamps();
    }

    public function storagePath(): string
    {
        return 'ingredients/' . $this->id;
    }

    public function imageDocument(): BelongsTo {
        return $this->belongsTo(Document::class, 'image_id');
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'ingredient_recipe')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }
}
