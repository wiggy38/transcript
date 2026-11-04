<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal des actions du back-office (cahier §5) : ajustement de quota, remboursement.
     * Une ligne ne se modifie pas. Les clés étrangères passent à nul si la cible disparaît,
     * pour que la trace reste lisible.
     */
    public function up(): void
    {
        Schema::create('journal_administration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transcription_id')->nullable()->constrained('transcriptions')->nullOnDelete();
            $table->string('action', 40);
            $table->text('motif');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_administration');
    }
};
