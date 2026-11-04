<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quotas : date d'abonnement Pro (ancre du cycle mensuel), achats de Pack (secondes, expiration),
     * et trois colonnes sur le journal : plan et période auxquels la ligne appartient, Pack consommé.
     * Un remboursement recopie ces trois valeurs, pour rendre le volume à la bonne période et au bon Pack.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('abonnement_debut_at')->nullable()->after('plan');
        });

        Schema::create('pack_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('secondes');
            $table->timestamp('achete_at');
            $table->timestamp('expire_at');
            $table->timestamps();

            // Consommation : les Packs valables, le plus ancien d'abord (expire_at).
            $table->index(['user_id', 'expire_at']);
        });

        Schema::table('usage_records', function (Blueprint $table) {
            $table->string('plan', 20)->nullable()->after('unite');
            $table->date('periode')->nullable()->after('plan');
            $table->foreignId('pack_achat_id')->nullable()->after('periode')
                ->constrained('pack_achats')->nullOnDelete();

            $table->index(['user_id', 'unite', 'plan', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'unite', 'plan', 'periode']);
            $table->dropConstrainedForeignId('pack_achat_id');
            $table->dropColumn(['plan', 'periode']);
        });

        Schema::dropIfExists('pack_achats');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('abonnement_debut_at');
        });
    }
};
