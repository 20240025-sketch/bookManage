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
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->integer('publication_year')->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('reading_status')->default('unread');
            $table->timestamps();

            // インデックス
            $table->index(['title']);
            $table->index(['author']);
            $table->index(['category']);
            $table->index(['reading_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
