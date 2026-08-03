<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Chave de vínculo com a Mentorfy. NUNCA casar por e-mail: e-mail
            // muda e pode ser reaproveitado — um mentor herdaria os formulários
            // de outro.
            $table->string('mentorfy_profile_id')->nullable()->unique()->after('email');
        });

        Schema::table('workspaces', function (Blueprint $table) {
            // O workspace pertence ao mentor DONO, não a quem logou primeiro.
            // Localizar por aqui deixa o provisionamento independente da ordem
            // de acesso entre mentor e membros do time dele.
            $table->string('mentorfy_owner_profile_id')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mentorfy_profile_id']);
            $table->dropColumn('mentorfy_profile_id');
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropUnique(['mentorfy_owner_profile_id']);
            $table->dropColumn('mentorfy_owner_profile_id');
        });
    }
};
