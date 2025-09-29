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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // クラス名 (例: "1年A組")
            $table->year('nendo'); // 年度
            $table->integer('grade'); // 学年 (1-6)
            $table->string('kumi'); // 組 (A, B, C等)
            $table->timestamps();
            
            // 複合ユニーク制約: 同一年度内で同じ学年・組は重複不可
            $table->unique(['nendo', 'grade', 'kumi']);
            
            // インデックスを追加
            $table->index('nendo');
            $table->index('grade');
            $table->index(['grade', 'kumi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
