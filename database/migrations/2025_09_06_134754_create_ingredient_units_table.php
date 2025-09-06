<?php

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
        Schema::create('ingredient_units', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Ingredient::class)->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->boolean('is_public')->default(false);
            $table->float('gram_per_unit');
            $table->string('unit_name');
            $table->string('unit_name_abbr')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_units');
    }
};
