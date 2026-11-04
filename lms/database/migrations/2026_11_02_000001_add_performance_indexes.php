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
        Schema::table('courses', function (Blueprint $table) {
            // Dashboard dosen menampilkan daftar mata kuliah berdasarkan lecturer_id dan status aktif.
            $table->index(['lecturer_id', 'status']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            // Query daftar tugas per course sering difilter berdasarkan status untuk menampilkan tugas aktif.
            $table->index(['course_id', 'status']);
            $table->index('status', 'assignments_status_index');
        });

        Schema::table('submissions', function (Blueprint $table) {
            // Query rekap nilai dan monitoring pengumpulan biasanya mengambil item terbaru per assignment
            // dan mengurutkan berdasarkan submitted_at.
            $table->index(['assignment_id', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['assignment_id', 'submitted_at']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'status']);
            $table->dropIndex('assignments_status_index');
        });

        if (! Schema::hasIndex('courses', ['lecturer_id'])) {
            Schema::table('courses', function (Blueprint $table) {
                $table->index('lecturer_id');
            });
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['lecturer_id', 'status']);
        });
    }
};