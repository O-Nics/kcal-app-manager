<?php

use App\Models\IngredientType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();

            $table->boolean('verified')->default(false);

            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignIdFor(IngredientType::class)->constrained()->cascadeOnDelete();
            $table->foreignId('image_id')->nullable()->constrained('documents')->nullOnDelete();


            $table->string('name');


            $table->string('product_quantity_unit');
            $table->string('barcode')->nullable();


            $table->double('product_quantity')->nullable();
            $table->double('energy_kcal100g');
            $table->double('energy_kj100g')->nullable();

            $table->double('fat')->nullable();
            $table->double('saturated_fat')->nullable();
            $table->double('sugars')->nullable();
            $table->double('carbs')->nullable();
            $table->double('fiber')->nullable();
            $table->double('proteins')->nullable();
            $table->double('salt')->nullable();
            $table->double('sodium')->nullable();

            // Minéraux
            $table->double('calcium')->nullable();
            $table->double('iron')->nullable();
            $table->double('copper')->nullable();
            $table->double('potassium')->nullable();
            $table->double('magnesium')->nullable();
            $table->double('manganese')->nullable();
            $table->double('phosphorus')->nullable();
            $table->double('zinc')->nullable();
            $table->double('selenium')->nullable();
            $table->double('iodine')->nullable();
            $table->double('fluoride')->nullable();
            $table->double('chromium')->nullable();
            $table->double('cobalt')->nullable();
            $table->double('molybdenum')->nullable();
            $table->double('chloride')->nullable();
            // rares
            $table->double('arsenic')->nullable();
            $table->double('boron')->nullable();
            $table->double('rubidium')->nullable();
            $table->double('tin')->nullable();
            $table->double('vanadium')->nullable();

            // Vitamines
            $table->double('vitamin_a')->nullable();
            $table->double('vitamin_d')->nullable();
            $table->double('vitamin_e')->nullable();
            $table->double('vitamin_k')->nullable();
            $table->double('vitamin_c')->nullable();
            $table->double('vitamin_b1')->nullable();
            $table->double('vitamin_b2')->nullable();
            $table->double('vitamin_b3')->nullable();
            $table->double('pantothenic_acid')->nullable();
            $table->double('vitamin_b6')->nullable();
            $table->double('vitamin_b9')->nullable();
            $table->double('vitamin_b12')->nullable();
            $table->double('biotin')->nullable();
            $table->double('choline')->nullable();

            // Lipides détaillés (optionnels mais utiles)
            $table->double('mono_fat')->nullable();
            $table->double('poly_fat')->nullable();
            $table->double('trans_fat')->nullable();
            $table->double('omega3_ala')->nullable();
            $table->double('omega3_epa')->nullable();
            $table->double('omega3_dha')->nullable();
            $table->double('omega6_la')->nullable();

            // Sucres détaillés
            $table->double('lactose')->nullable();
            $table->double('maltose')->nullable();
            $table->double('fructose')->nullable();
            $table->double('glucose')->nullable();
            $table->double('sucrose')->nullable();
            $table->double('galactose')->nullable();
            $table->double('polyols')->nullable();
            $table->double('starch')->nullable();

            // Autres
            $table->double('cholesterol')->nullable();
            $table->double('water')->nullable();
            $table->double('alcohol')->nullable();
            $table->double('caffeine')->nullable();

            $table->json('nutriscore')->nullable();
            $table->json('environmental_score')->nullable();



            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
