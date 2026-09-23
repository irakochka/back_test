<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('referrer_master_id')
                ->constrained('masters')
                ->cascadeOnDelete();

            $table->foreignId('referred_master_id')
                ->constrained('masters')
                ->cascadeOnDelete();

            $table->foreignId('referral_id')
                ->constrained('referrals')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            $table->integer('payment_amount');
            $table->integer('amount');
            $table->unsignedTinyInteger('percent');

            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
    }
};
