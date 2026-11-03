<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comptes : lien collé avant l'inscription (repris après la vérification),
     * adresse IP de création (limite de comptes gratuits), identifiant Google
     * (connexion optionnelle). Le mot de passe devient facultatif pour les comptes
     * créés avec Google.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('google_id')->nullable()->unique()->after('email_verified_at');
            $table->string('inscription_ip', 45)->nullable()->index()->after('google_id');
            $table->string('lien_en_attente', 2048)->nullable()->after('inscription_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'inscription_ip', 'lien_en_attente']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
