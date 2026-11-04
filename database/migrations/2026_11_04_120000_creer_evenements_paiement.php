<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal des webhooks du prestataire. « event_id » est unique : le même événement reçu deux fois
     * ne crée qu'une ligne et n'est traité qu'une fois (traite_at renseigné à la fin du traitement).
     */
    public function up(): void
    {
        Schema::create('evenements_paiement', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique();
            $table->string('type', 100);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('charge');
            $table->timestamp('recu_at');
            $table->timestamp('traite_at')->nullable();
            $table->unsignedSmallInteger('tentatives')->default(0);
            $table->text('erreur')->nullable();
            $table->timestamps();

            $table->index(['type', 'recu_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_paiement');
    }
};
