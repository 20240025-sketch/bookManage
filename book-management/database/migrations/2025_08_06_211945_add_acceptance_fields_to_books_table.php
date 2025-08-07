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
        Schema::table('books', function (Blueprint $table) {
            $table->date('acceptance_date')->nullable()->comment('受け入れ日');
            $table->string('acceptance_type')->nullable()->comment('受け入れ種別');
            $table->string('acceptance_source')->nullable()->comment('受け入れ元');
            $table->string('discard')->nullable()->comment('廃棄情報');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['acceptance_date', 'acceptance_type', 'acceptance_source', 'discard']);
        });
    }
};
