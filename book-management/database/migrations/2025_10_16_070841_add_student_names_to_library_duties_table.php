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
            $table->string('student_name_1')->nullable()->after('duty_date');
            $table->string('student_name_2')->nullable()->after('student_name_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_duties', function (Blueprint $table) {
            $table->dropColumn(['student_name_1', 'student_name_2']);
        });
    }
};
