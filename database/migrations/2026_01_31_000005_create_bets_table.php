cost<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('bets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('game_id')->constrained()->cascadeOnDelete();
                $table->integer('eisbaeren_goals')->comment('User prediction');
                $table->integer('opponent_goals')->comment('User prediction');

                // Joker system
                $table->string('joker_type')->nullable()->comment('safety, double_down, copy_cat, etc.');
                $table->json('joker_data')->nullable()->comment('Additional joker metadata');

                // Pricing
                $table->decimal('base_price', 4, 2)->default(0)->comment('Original 0-1€ price');
                $table->decimal('multiplier', 4, 2)->default(1.0)->comment('Season phase + joker multiplier');
                $table->decimal('final_price', 4, 2)->default(0)->comment('Calculated final cost');

                $table->timestamps();

                // Constraints
                $table->unique(['user_id', 'game_id'], 'user_game_unique');
                $table->index('game_id');
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('bets');
        }
    };
