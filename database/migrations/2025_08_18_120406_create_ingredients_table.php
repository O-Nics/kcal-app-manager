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
            $table->id();
            $table->boolean('verified')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignIdFor(IngredientType::class)->constrained()->cascadeOnDelete();


            $table->string('name');
            $table->string('default_unit')->default('g'); // 'g' ou 'ml'
            $table->integer('reference_value')->default(100); // 100g ou 100ml`

            $table->integer('kcal_for_100');
            $table->string('barcode')->nullable();
//            $table->string('brand')->nullable();
            $table->longText('conservation_conditions')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->boolean('is_ai')->default(false);
            $table->string('product_quantity_unit')->nullable();
            $table->double('product_quantity')->nullable();
            $table->double('fat'); // in g
            $table->double('saturated_fat'); // in g
            $table->double('mono_saturated_fat')->nullable(); // in g
            $table->double('poly_in_saturated_fat')->nullable(); // in g
            $table->double('glucoside'); // in g
            $table->double('sugar'); // in g
            $table->double('fiber'); // in g
            $table->double('protein'); // in g
            $table->double('salt')->nullable(); // in g
            $table->double('sodium')->nullable(); // in g
            $table->double('iron')->nullable(); // in g
            $table->double('copper')->nullable(); // in g
            $table->double('vitamin_a')->nullable(); // in µg
            $table->double('vitamin_c')->nullable(); // in mg
            $table->double('vitamin_d')->nullable(); // in mg
            $table->double('vitamin_b1')->nullable(); // in mg
            $table->double('vitamin_b2')->nullable(); // in mg
            $table->double('vitamin_b3')->nullable(); // in mg
            $table->double('vitamin_b5')->nullable(); // in mg
            $table->double('vitamin_b6')->nullable(); // in mg
            $table->double('vitamin_b7')->nullable(); // in mg
            $table->double('vitamin_b9')->nullable(); // in mg
            $table->double('vitamin_b11')->nullable(); // in mg
            $table->double('vitamin_b12')->nullable(); // in mg
            $table->double('vitamin_e')->nullable(); // in mg
            $table->double('vitamin_k')->nullable(); // in µg
            $table->double('calcium')->nullable();        // Calcium (mg)
            $table->double('fer')->nullable();            // Fer (mg)
            $table->double('arsenic')->nullable();        // Arsenic (µg)
            $table->double('bore')->nullable();           // Bore (mg)
            $table->double('biotin')->nullable();        // Biotine (µg)
            $table->double('choline')->nullable();        // Choline (mg)
            $table->double('cholesterol')->nullable();        // Choline (mg)
            $table->double('chlorure')->nullable();       // Chlorure (mg)
            $table->double('chrome')->nullable();         // Chrome (mg)
            $table->double('cobalt')->nullable();         // Cobalt (µg)
            $table->double('polyols')->nullable();         // Cobalt (µg)
            $table->double('cuivre')->nullable();         // Cuivre (mg)
            $table->double('fluorure')->nullable();       // Fluorure (mg)
            $table->double('fluor')->nullable();          // Fluor (mg)
            $table->double('iode')->nullable();           // Iode (µg)
            $table->double('magnesium')->nullable();      // Magnésium (mg)
            $table->double('manganese')->nullable();      // Manganèse (mg)
            $table->double('lactose')->nullable();      // Manganèse (mg)
            $table->double('maltose')->nullable();      // Manganèse (mg)
            $table->double('glucose')->nullable();      // Manganèse (mg)
            $table->double('fructose')->nullable();      // Manganèse (mg)
            $table->double('molybdene')->nullable();      // Molybdène (µg)
            $table->double('phosphorus')->nullable();      // Phosphore (mg)
            $table->double('potassium')->nullable();      // Potassium (mg)
            $table->double('rubidium')->nullable();       // Rubidium (µg)
            $table->double('selenium')->nullable();       // Sélénium (µg)
            $table->double('silicone')->nullable();       // Silicone (mg)
            $table->double('soufre')->nullable();         // Soufre (mg)
            $table->double('pantothenic_acid')->nullable();         // Soufre (mg)
            $table->double('etain')->nullable();          // Étain (mg)
            $table->double('vanadium')->nullable();       // Vanadium (µg)
            $table->double('zinc')->nullable();           // Zinc (mg)
            $table->double('eau')->nullable();            // Eau (ml)
            $table->double('alcool')->nullable();         // Alcool (ml)

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
