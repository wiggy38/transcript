<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal de consommation, jamais modifié. Un remboursement est une ligne
     * en sens inverse (quantité négative) qui pointe vers la ligne annulée.
     *
     * transcription_id et script_analysis_id deviennent nuls à la suppression
     * de la transcription : le quota consommé reste compté.
     */
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transcription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('script_analysis_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('annule_usage_record_id')->nullable()->unique()
                ->constrained('usage_records')->nullOnDelete();
            $table->string('unite', 20);
            $table->bigInteger('quantite');
            $table->string('motif', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'unite', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
