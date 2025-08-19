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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('created_by')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('difficulty')->nullable();

            $table->json('steps')->nullable();
            $table->json('tags')->nullable();

            $table->integer('portion');

            $table->float('time')->nullable();

            $table->boolean('verified')->default(false);
            $table->boolean('is_private')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
