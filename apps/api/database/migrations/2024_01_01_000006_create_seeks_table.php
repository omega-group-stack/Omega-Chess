<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->string('mode', 20)->default('casual');
            $table->string('color', 10)->default('random');
            $table->string('time_control', 32)->default('untimed');
            $table->unsignedBigInteger('initial_time_ms')->nullable();
            $table->unsignedBigInteger('increment_ms')->default(0);
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('seeks'); }
};
