<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaction Paddle d'un achat de Pack, unique : un même paiement ne crée jamais deux Packs.
     */
    public function up(): void
    {
        Schema::table('pack_achats', function (Blueprint $table) {
            $table->string('paddle_transaction_id', 100)->nullable()->unique()->after('expire_at');
        });
    }

    public function down(): void
    {
        Schema::table('pack_achats', function (Blueprint $table) {
            $table->dropUnique(['paddle_transaction_id']);
            $table->dropColumn('paddle_transaction_id');
        });
    }
};
