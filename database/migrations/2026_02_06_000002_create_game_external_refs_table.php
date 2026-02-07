<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_external_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();

            $table->string('source');      // 'penny', 'eisbaeren'
            $table->string('external_id'); // e.g. details id / stable id if available
            $table->string('external_url')->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id'], 'game_external_refs_unique_source_id');
            $table->index(['game_id', 'source'], 'game_external_refs_game_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_external_refs');
    }
};
