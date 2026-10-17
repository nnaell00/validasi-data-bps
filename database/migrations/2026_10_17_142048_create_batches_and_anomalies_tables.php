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
        Schema::create('validation_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('validation_profile_id')->constrained('validation_profiles')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('total_rows')->default(0);
            $table->string('status', 30)->default('uploaded'); // uploaded, validated, completed
            $table->json('headers')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validation_batch_id')->constrained('validation_batches')->cascadeOnDelete();
            $table->integer('row_index')->default(0);
            $table->integer('row_number')->default(1);
            $table->json('original_data'); // Data asli sumber (tidak diubah otomatis)
            $table->json('current_data');  // Data hasil koreksi petugas
            $table->boolean('has_anomalies')->default(false);
            $table->boolean('is_corrected')->default(false);
            $table->timestamps();
        });

        Schema::create('row_anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validation_batch_id')->constrained('validation_batches')->cascadeOnDelete();
            $table->foreignId('batch_row_id')->constrained('batch_rows')->cascadeOnDelete();
            $table->string('column_name', 100);
            $table->integer('column_index')->default(0);
            $table->text('invalid_value')->nullable();
            $table->string('error_message');
            $table->boolean('needs_confirmation')->default(false);
            $table->text('confirmation_note')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->text('corrected_value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validation_batch_id')->constrained('validation_batches')->cascadeOnDelete();
            $table->foreignId('batch_row_id')->nullable()->constrained('batch_rows')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('column_name', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('action', 50)->default('correction'); // correction, revalidation, export
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('row_anomalies');
        Schema::dropIfExists('batch_rows');
        Schema::dropIfExists('validation_batches');
    }
};
