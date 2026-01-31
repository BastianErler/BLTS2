<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile')->nullable()->after('email');
            $table->boolean('wants_email_reminder')->default(false)->after('mobile');
            $table->boolean('wants_sms_reminder')->default(false)->after('wants_email_reminder');
            $table->boolean('is_admin')->default(false)->after('wants_sms_reminder');
            $table->decimal('balance', 10, 2)->default(0)->after('is_admin')->comment('Current account balance');
            
            // Joker system
            $table->integer('jokers_remaining')->default(3)->after('balance');
            $table->json('jokers_used')->nullable()->after('jokers_remaining')->comment('Track which jokers were used when');
            
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile',
                'wants_email_reminder', 
                'wants_sms_reminder',
                'is_admin',
                'balance',
                'jokers_remaining',
                'jokers_used',
                'deleted_at'
            ]);
        });
    }
};
