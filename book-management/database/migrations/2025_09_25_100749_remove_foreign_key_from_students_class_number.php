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
            // 外部キー制約を削除
            $table->dropForeign(['class_number']);
            
            // class_numberカラムを通常のintegerに変更（制約なし）
            $table->integer('class_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 外部キー制約を再追加
            $table->foreign('class_number')->references('id')->on('classes')->onDelete('set null');
        });
    }
};
