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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('name');
            $table->string('name_transcription')->nullable();
            $table->string('email')->unique();
            $table->string('grade')->nullable();
            $table->string('class')->nullable();
            $table->timestamps();
            
            // インデックスを追加
            $table->index('student_number');
            $table->index('name');
            $table->index(['grade', 'class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
