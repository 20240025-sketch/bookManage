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
            // 外部キー制約を一時的に削除
            $table->dropForeign(['class_id']);
            
            // カラム名を変更
            $table->renameColumn('class_id', 'class_number');
        });
        
        // 外部キー制約を再作成
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_number')->references('id')->on('classes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['class_number']);
            
            // カラム名を元に戻す
            $table->renameColumn('class_number', 'class_id');
        });
        
        // 外部キー制約を再作成
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
        });
    }
};
