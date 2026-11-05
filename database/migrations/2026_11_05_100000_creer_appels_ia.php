<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Journal de chaque appel au modèle de langage, tentatives comprises (réussies ou non). */
    public function up(): void
    {
        Schema::create('appels_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('ai_output_id')->nullable()->index();
            $table->string('fonctionnalite', 40)->index();
            $table->string('fournisseur', 40);
            $table->string('modele', 80);
            $table->unsignedTinyInteger('tentative');
            $table->unsignedInteger('tokens_entree')->default(0);
            $table->unsignedInteger('tokens_sortie')->default(0);
            $table->unsignedInteger('duree_ms')->default(0);
            $table->decimal('cout_estime_usd', 10, 6)->default(0);
            $table->string('statut', 40);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appels_ia');
    }
};
