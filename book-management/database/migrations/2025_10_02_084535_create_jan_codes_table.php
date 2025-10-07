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
        Schema::create('jan_codes', function (Blueprint $table) {
            $table->id();
            $table->string('jan_code', 13)->unique(); // EAN-13フォーマット
            $table->integer('sequence_number'); // 連番管理
            $table->boolean('is_used')->default(false); // 使用済みフラグ
            $table->unsignedBigInteger('book_id')->nullable(); // 関連書籍ID
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books')->onDelete('set null');
            $table->index(['jan_code']);
            $table->index(['sequence_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jan_codes');
    }
};
