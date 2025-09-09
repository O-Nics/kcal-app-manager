<?php

namespace App\Models;

use App\Support\Traits\DocumentTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;


/**
 * Class Ingredient
 *
 * @property bool $verified
 * @property int|null $customer_id
 * @property int|null $ingredient_type_id
 * @property int|null $image_id
 * @property string|null $name
 * @property string $product_quantity_unit
 * @property string|null $barcode
 * @property int $reference_value
 * @property array|null $nutriscore
 * @property array|null $environmental_score
 *
 * @property float|null $product_quantity
 * @property float|null $energy_kcal100g
 * @property float|null $energy_kj100g
 * @property float|null $fat
 * @property float|null $saturated_fat
 * @property float|null $sugars
 * @property float|null $carbs
 * @property float|null $fiber
 * @property float|null $proteins
 * @property float|null $salt
 * @property float|null $sodium
 * @property float|null $energy_kcal
 * @property float|null $energy_kj
 * @property float|null $calcium
 * @property float|null $iron
 * @property float|null $copper
 * @property float|null $potassium
 * @property float|null $magnesium
 * @property float|null $manganese
 * @property float|null $phosphorus
 * @property float|null $zinc
 * @property float|null $selenium
 * @property float|null $iodine
 * @property float|null $fluoride
 * @property float|null $chromium
 * @property float|null $cobalt
 * @property float|null $molybdenum
 * @property float|null $chloride
 * @property float|null $arsenic
 * @property float|null $boron
 * @property float|null $rubidium
 * @property float|null $tin
 * @property float|null $vanadium
 * @property float|null $vitamin_a
 * @property float|null $vitamin_d
 * @property float|null $vitamin_e
 * @property float|null $vitamin_k
 * @property float|null $vitamin_c
 * @property float|null $vitamin_b1
 * @property float|null $vitamin_b2
 * @property float|null $vitamin_b3
 * @property float|null $pantothenic_acid
 * @property float|null $vitamin_b6
 * @property float|null $vitamin_b9
 * @property float|null $vitamin_b12
 * @property float|null $biotin
 * @property float|null $choline
 * @property float|null $mono_fat
 * @property float|null $poly_fat
 * @property float|null $trans_fat
 * @property float|null $omega3_ala
 * @property float|null $omega3_epa
 * @property float|null $omega3_dha
 * @property float|null $omega6_la
 * @property float|null $lactose
 * @property float|null $maltose
 * @property float|null $fructose
 * @property float|null $glucose
 * @property float|null $sucrose
 * @property float|null $galactose
 * @property float|null $polyols
 * @property float|null $starch
 * @property float|null $cholesterol
 * @property float|null $water
 * @property float|null $alcohol
 * @property float|null $caffeine
 * @property-read Customer|null $customer
 * @property-read IngredientType|null $type
 * @property-read Document|null $image
 * @property-read Collection|Brand[] $brands
 * @property-read Collection|Recipe[] $recipes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Ingredient extends Model implements Documentable
{

    use HasFactory, DocumentTrait, SoftDeletes;

    protected $fillable = [
        'verified',
        'customer_id',
        'ingredient_type_id',
        'image_id',
        'name',
        'nutriscore',
        'environmental_score',
        'product_quantity_unit',
        'product_quantity',
        'energy_kcal100g',
        'energy_kj100g',
        'barcode',
        'reference_value',
        'ingredients',

        // Macro & énergie
        'fat',
        'saturated_fat',
        'sugars',
        'carbs',
        'fiber',
        'proteins',
        'salt',
        'sodium',
        'energy_kcal',
        'energy_kj',

        // Minéraux
        'calcium',
        'iron',
        'copper',
        'potassium',
        'magnesium',
        'manganese',
        'phosphorus',
        'zinc',
        'selenium',
        'iodine',
        'fluoride',
        'chromium',
        'cobalt',
        'molybdenum',
        'chloride',
        'arsenic',
        'boron',
        'rubidium',
        'tin',
        'vanadium',

        // Vitamines
        'vitamin_a',
        'vitamin_d',
        'vitamin_e',
        'vitamin_k',
        'vitamin_c',
        'vitamin_b1',
        'vitamin_b2',
        'vitamin_b3',
        'pantothenic_acid',
        'vitamin_b6',
        'vitamin_b9',
        'vitamin_b12',
        'biotin',
        'choline',

        // Lipides détaillés
        'mono_fat',
        'poly_fat',
        'trans_fat',
        'omega3_ala',
        'omega3_epa',
        'omega3_dha',
        'omega6_la',

        // Sucres détaillés
        'lactose',
        'maltose',
        'fructose',
        'glucose',
        'sucrose',
        'galactose',
        'polyols',
        'starch',

        // Autres
        'cholesterol',
        'water',
        'alcohol',
        'caffeine',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'nutriscore' => 'array',
        'environmental_score' => 'array',
        'reference_value' => 'integer',

        // macro & énergie
        'product_quantity' => 'float',
        'energy_kj_100g' => 'float',
        'energy_kcal_100g' => 'float',
        'fat' => 'float',
        'saturated_fat' => 'float',
        'sugars' => 'float',
        'carbs' => 'float',
        'fiber' => 'float',
        'proteins' => 'float',
        'salt' => 'float',
        'sodium' => 'float',
        'energy_kcal' => 'float',
        'energy_kj' => 'float',

        // Minéraux
        'calcium' => 'float',
        'iron' => 'float',
        'copper' => 'float',
        'potassium' => 'float',
        'magnesium' => 'float',
        'manganese' => 'float',
        'phosphorus' => 'float',
        'zinc' => 'float',
        'selenium' => 'float',
        'iodine' => 'float',
        'fluoride' => 'float',
        'chromium' => 'float',
        'cobalt' => 'float',
        'molybdenum' => 'float',
        'chloride' => 'float',
        'arsenic' => 'float',
        'boron' => 'float',
        'rubidium' => 'float',
        'tin' => 'float',
        'vanadium' => 'float',

        // Vitamines
        'vitamin_a' => 'float',
        'vitamin_d' => 'float',
        'vitamin_e' => 'float',
        'vitamin_k' => 'float',
        'vitamin_c' => 'float',
        'vitamin_b1' => 'float',
        'vitamin_b2' => 'float',
        'vitamin_b3' => 'float',
        'pantothenic_acid' => 'float',
        'vitamin_b6' => 'float',
        'vitamin_b9' => 'float',
        'vitamin_b12' => 'float',
        'biotin' => 'float',
        'choline' => 'float',

        // Lipides détaillés
        'mono_fat' => 'float',
        'poly_fat' => 'float',
        'trans_fat' => 'float',
        'omega3_ala' => 'float',
        'omega3_epa' => 'float',
        'omega3_dha' => 'float',
        'omega6_la' => 'float',

        // Sucres détaillés
        'lactose' => 'float',
        'maltose' => 'float',
        'fructose' => 'float',
        'glucose' => 'float',
        'sucrose' => 'float',
        'galactose' => 'float',
        'polyols' => 'float',
        'starch' => 'float',

        // Autres
        'cholesterol' => 'float',
        'water' => 'float',
        'alcohol' => 'float',
        'caffeine' => 'float',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IngredientType::class, 'ingredient_type_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'image_id');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_ingredients')->withTimestamps();
    }

    public function storagePath(): string
    {
        return 'ingredients/' . $this->id;
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'ingredient_recipe')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }
}
