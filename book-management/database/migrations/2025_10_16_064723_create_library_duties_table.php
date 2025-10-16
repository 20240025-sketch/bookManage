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
        Schema::create('library_duties', function (Blueprint $table) {
            $table->id();
            $table->date('duty_date')->unique()->comment('当番日付');
            $table->integer('visitor_count')->default(0)->comment('利用者数');
            $table->integer('borrow_count')->default(0)->comment('貸出人数');
            $table->text('reflection')->nullable()->comment('ふりかえり');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null')->comment('担当者（生徒ID）');
            $table->timestamps();
            
            $table->index('duty_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_duties');
    }
};
