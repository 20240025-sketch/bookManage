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
            // class_idカラムを追加（nullableで追加）
            $table->foreignId('class_id')->nullable()->after('name_transcription')->constrained('classes')->onDelete('set null');
            
            // 既存のgrade, classカラムはすぐには削除せず、後で削除予定のコメントを追加
            // TODO: データ移行完了後にgrade, classカラムを削除する
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });
    }
};
