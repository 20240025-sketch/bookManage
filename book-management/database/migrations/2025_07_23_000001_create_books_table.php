<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_transcription')->nullable(); // タイトルのヨミ
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->date('published_date')->nullable();
            $table->string('isbn', 20)->nullable();
            $table->integer('pages')->nullable();
            $table->decimal('price', 8, 2)->nullable(); // 価格
            $table->string('extent')->nullable(); // 大きさ・ページ数等の詳細
            $table->string('ndc', 10)->nullable(); // 日本十進分類法
            $table->string('reading_status')->default('unread');
            $table->timestamps();

            // インデックス
            $table->index(['title']);
            $table->index(['author']);
            $table->index(['reading_status']);
            $table->index(['ndc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
