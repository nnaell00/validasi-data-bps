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
        Schema::create('validation_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('validation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validation_profile_id')->constrained('validation_profiles')->cascadeOnDelete();
            $table->string('column_name', 100);
            $table->integer('column_index')->default(0);
            $table->string('data_type', 50)->default('string'); // string, numeric, date, enum
            $table->boolean('is_required')->default(true);
            $table->string('validation_type', 50)->default('none'); // none, regex, in_list, date_format, numeric_range, digits_length, phone
            $table->json('parameters')->nullable();
            $table->string('error_message')->nullable();
            $table->boolean('needs_confirmation')->default(false);
            $table->text('confirmation_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_rules');
        Schema::dropIfExists('validation_profiles');
    }
};
