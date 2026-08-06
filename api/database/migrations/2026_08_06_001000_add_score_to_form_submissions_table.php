<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('form_submissions', 'score')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->decimal('score', 4, 1)->nullable();
                // Analytics averages and per-tier counts always scope by form.
                $table->index(['form_id', 'score']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('form_submissions', 'score')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropIndex(['form_id', 'score']);
                $table->dropColumn('score');
            });
        }
    }
};
