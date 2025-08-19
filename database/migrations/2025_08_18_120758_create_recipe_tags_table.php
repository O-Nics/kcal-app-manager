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
        Schema::create('recipe_tags', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(\App\Models\Recipe::class)->constrained()->onDelete('cascade'); // Clé étrangère vers recipes
            $table->foreignIdFor(\App\Models\Tag::class)->constrained()->onDelete('cascade'); // Clé étrangère vers tags

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_tags');
    }
};
