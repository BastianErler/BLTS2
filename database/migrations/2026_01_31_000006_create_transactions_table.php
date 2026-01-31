<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete()->comment('Admin who created this transaction');
            $table->enum('type', ['deposit', 'withdrawal', 'bet_cost', 'bet_refund', 'season_payout', 'joker_bonus']);
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->foreignId('bet_id')->nullable()->constrained()->nullOnDelete()->comment('Related bet if applicable');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
