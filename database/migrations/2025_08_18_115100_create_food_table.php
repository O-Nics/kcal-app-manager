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
        Schema::create('food', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->uuidMorphs('foodable');
            $table->double('quantity');
            $table->string('unit');
            $table->foreignIdFor(\App\Models\MealType::class)->constrained()->cascadeOnDelete();
            $table->timestamp('added_at');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food');
    }
};
