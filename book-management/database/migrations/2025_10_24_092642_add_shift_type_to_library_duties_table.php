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
            // 既存のunique制約を削除
            $table->dropUnique(['duty_date']);
            
            // shift_typeカラムを追加（昼休み/放課後）
            $table->enum('shift_type', ['lunch', 'after_school'])
                ->default('lunch')
                ->after('duty_date')
                ->comment('勤務時間帯（昼休み/放課後）');
            
            // duty_date + shift_typeの複合ユニーク制約を追加
            $table->unique(['duty_date', 'shift_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_duties', function (Blueprint $table) {
            // 複合ユニーク制約を削除
            $table->dropUnique(['duty_date', 'shift_type']);
            
            // shift_typeカラムを削除
            $table->dropColumn('shift_type');
            
            // 元のunique制約を復元
            $table->unique('duty_date');
        });
    }
};
