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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Goal::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(\App\Models\ActivityLevel::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignId('profile_image_id')->nullable()->constrained('documents')->nullOnDelete();

            $table->enum('gender', ['male','female','other'])->nullable();

            $table->string('unique_key',36)->unique();
            $table->string('username')->nullable();
            $table->string('profile_password')->nullable();
            $table->string('activity')->nullable();
            $table->string('height')->nullable();
            $table->string('type_water')->nullable();// percent

            $table->integer('obj_kcals')->nullable();
            $table->integer('obj_steps')->nullable();
            $table->integer('obj_loss_weight_hebdo')->nullable();
            $table->integer('time_to_achieve')->nullable();

            $table->double('start_weight')->nullable();
            $table->double('wanted_weight')->nullable();
            $table->double('obj_water')->nullable();
            $table->double('volume_water')->nullable();// percent
            $table->double('volume_water_by_each')->nullable();// percent
            $table->double('obj_proteins')->nullable();// percent
            $table->double('obj_lipids')->nullable();// percent
            $table->double('obj_glucides')->nullable(); // percent

            $table->boolean('is_owner')->default(false);
            $table->boolean('profile_locked')->default(false);
            $table->boolean('accept_marketing')->default(false);
            $table->boolean('filled_first_form')->default(false);

            $table->date('birth_date')->nullable();
            $table->date('form_passed_at')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_paywall_at')->nullable();
            $table->timestamp('rated_app_at')->nullable();

            $table->string('picture_from_provider')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
