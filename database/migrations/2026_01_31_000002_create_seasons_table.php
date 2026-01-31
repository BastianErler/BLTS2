<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 10); // e.g. "24/25"
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Season phase multipliers (for excitement features)
            $table->decimal('phase_1_multiplier', 3, 1)->default(1.0);
            $table->decimal('phase_2_multiplier', 3, 1)->default(1.5);
            $table->decimal('phase_3_multiplier', 3, 1)->default(2.0);
            $table->decimal('playoff_multiplier', 3, 1)->default(3.0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
