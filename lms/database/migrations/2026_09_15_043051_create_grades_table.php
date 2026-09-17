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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')
                ->unique()
                ->constrained('submissions')
                ->cascadeOnDelete();
            $table->foreignId('graded_by')
                ->constrained('users');
            $table->decimal('score');
            $table->text('feedback')->nullable();
            $table->dateTime('graded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};