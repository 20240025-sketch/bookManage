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
        Schema::table('classes', function (Blueprint $table) {
            // ユニーク制約を削除
            $table->dropUnique(['nendo', 'grade', 'kumi']);
            
            // class_numberフィールドを追加
            $table->integer('class_number')->nullable()->after('kumi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // class_numberフィールドを削除
            $table->dropColumn('class_number');
            
            // ユニーク制約を再追加
            $table->unique(['nendo', 'grade', 'kumi']);
        });
    }
};
