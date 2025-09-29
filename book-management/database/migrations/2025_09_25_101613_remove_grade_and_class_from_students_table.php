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
        Schema::table('students', function (Blueprint $table) {
            // まずインデックスを削除
            $table->dropIndex('students_grade_class_index');
            
            // その後カラムを削除
            $table->dropColumn(['grade', 'class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // カラムを再追加（ロールバック用）
            $table->string('grade')->nullable();
            $table->string('class')->nullable();
            
            // インデックスを再追加
            $table->index(['grade', 'class']);
        });
    }
};
