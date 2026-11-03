<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Analyse de script (F7). Une seule analyse par transcription : relancer
     * une analyse réutilise la ligne.
     */
    public function up(): void
    {
        Schema::create('script_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcription_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('statut', 20)->default('en_attente');
            $table->string('fournisseur', 50)->nullable();
            $table->decimal('cout_estime_usd', 10, 4)->default(0);
            $table->timestamp('date_debut')->nullable();
            $table->timestamp('date_termine')->nullable();
            $table->timestamp('date_echec')->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_analyses');
    }
};
