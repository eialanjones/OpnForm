<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Portuguese is the product default. Forms created before this migration carry the
     * old 'en' default, which was never a deliberate choice by their owner, so they are
     * moved over as well. Owners can still pick any language per form in the editor.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('language')->default('pt')->change();
        });

        DB::table('forms')->where('language', 'en')->update(['language' => 'pt']);
    }

    public function down(): void
    {
        DB::table('forms')->where('language', 'pt')->update(['language' => 'en']);

        Schema::table('forms', function (Blueprint $table) {
            $table->string('language')->default('en')->change();
        });
    }
};
