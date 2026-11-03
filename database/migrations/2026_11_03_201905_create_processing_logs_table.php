<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne par étape de traitement (observabilité, cahier §6).
     */
    public function up(): void
    {
        Schema::create('processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcription_id')->constrained()->cascadeOnDelete();
            $table->string('etape', 30);
            $table->unsignedInteger('duree_ms')->nullable();
            $table->boolean('succes');
            $table->text('message')->nullable();
            $table->decimal('cout_usd', 10, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['transcription_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_logs');
    }
};
