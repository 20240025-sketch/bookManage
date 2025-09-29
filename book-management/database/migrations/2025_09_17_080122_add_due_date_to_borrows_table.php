<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            // まずはnullableで追加
            $table->date('due_date')->nullable()->after('borrowed_date');
            $table->index('due_date');
        });
        
        // 既存の貸出データに対して、借用日の14日後を返却期限日として設定
        DB::statement("UPDATE borrows SET due_date = DATE(borrowed_date, '+14 days') WHERE due_date IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropColumn('due_date');
        });
    }
};
