<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * One row per PDF a respondent asks the AI to write.
     *
     * `token` is the secret handed to the browser that started the generation:
     * polling the status and downloading the result both require it, so a
     * generation is never readable by another visitor of the same public form.
     */
    public function up(): void
    {
        Schema::create('form_ai_pdf_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Forms\Form::class, 'form_id')->constrained()->cascadeOnDelete();
            $table->uuid('block_id');
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->json('answers')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_reference')->nullable();
            $table->uuid('storage_uuid')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'block_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_ai_pdf_generations');
    }
};
