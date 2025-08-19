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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_name');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('number')->nullable();
            $table->string("token")->nullable();
            $table->string('thumbs')->nullable();
            $table->string('collection')->nullable();
            $table->boolean('is_private')->default(false);
            $table->morphs('documentable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
