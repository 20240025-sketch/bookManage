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
            // 利用者を生徒と教員に分ける
            $table->integer('student_visitor_count')->default(0)->after('visitor_count');
            $table->integer('teacher_visitor_count')->default(0)->after('student_visitor_count');
            
            // 担当者1の学年とクラス
            $table->string('grade_1')->nullable()->after('student_name_1');
            $table->string('class_1')->nullable()->after('grade_1');
            
            // 担当者2の学年とクラス
            $table->string('grade_2')->nullable()->after('student_name_2');
            $table->string('class_2')->nullable()->after('grade_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_duties', function (Blueprint $table) {
            $table->dropColumn([
                'student_visitor_count',
                'teacher_visitor_count',
                'grade_1',
                'class_1',
                'grade_2',
                'class_2'
            ]);
        });
    }
};
