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
        // SQLiteでは特定の名前のインデックスを削除
        DB::statement('DROP INDEX IF EXISTS books_reading_status_index');
        
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'reading_status')) {
                $table->dropColumn('reading_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('reading_status')->default('unread');
        });
    }
};
