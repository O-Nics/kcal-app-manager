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
        Schema::create('objective_kcal_meal_types', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('meal_type_id')->constrained('meal_types')->cascadeOnDelete();

            $table->unsignedInteger('kcal'); // objectif pour CE type
            $table->date('starts_at');       // inclusif
            $table->date('ends_at')->nullable(); // exclusif (ou inclusif, à toi de choisir, je prends exclusif)

            $table->timestamps();

            $table->unique(['customer_id','meal_type_id','starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objective_kcal_meal_types');
    }
};
