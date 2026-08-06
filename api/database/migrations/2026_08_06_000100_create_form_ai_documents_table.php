<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Knowledge sources and structure templates attached to an AI PDF block.
     *
     * Scoped by workspace + block_id rather than form_id: the block can be
     * configured on a form that has not been saved yet, and the block id is a
     * client generated UUID that exists from the moment the block is added.
     */
    public function up(): void
    {
        Schema::create('form_ai_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Workspace::class, 'workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('form_id')->nullable()->index();
            $table->uuid('block_id');
            $table->string('role', 20)->default('knowledge');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status', 20)->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('extracted_characters')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            // Every read is scoped to a tenant and a block, never a block alone.
            $table->index(['workspace_id', 'block_id']);
            $table->index(['block_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_ai_documents');
    }
};
