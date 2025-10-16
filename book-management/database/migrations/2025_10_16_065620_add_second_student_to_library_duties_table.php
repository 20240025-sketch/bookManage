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
        Schema::table('library_duties', function (Blueprint $table) {
            $table->foreignId('student_id_2')->nullable()->after('student_id')->constrained('students')->onDelete('set null')->comment('担当者2（生徒ID）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_duties', function (Blueprint $table) {
            $table->dropForeign(['student_id_2']);
            $table->dropColumn('student_id_2');
        });
    }
};
