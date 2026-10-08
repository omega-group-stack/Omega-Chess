<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedInteger('ply');
            $table->char('from_square', 2);
            $table->char('to_square', 2);
            $table->char('piece', 1);
            $table->char('captured', 1)->nullable();
            $table->char('promotion', 1)->nullable();
            $table->string('san', 12);
            $table->text('fen_after');
            $table->timestamps();
            $table->unique(['game_id', 'ply']);
        });
    }

    public function down(): void { Schema::dropIfExists('game_moves'); }
};
