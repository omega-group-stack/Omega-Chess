<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('white_user_id')->constrained('users');
            $table->foreignId('black_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 20)->default('practice');
            $table->string('status', 20)->default('active');
            $table->char('turn', 1)->default('w');
            $table->char('winner', 1)->nullable();
            $table->text('fen');
            $table->string('result', 40)->nullable();
            $table->timestamps();
            $table->index(['white_user_id', 'updated_at']);
            $table->index(['black_user_id', 'updated_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('games'); }
};
