<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('time_control', 32)->default('untimed');
            $table->unsignedBigInteger('initial_time_ms')->nullable();
            $table->unsignedBigInteger('increment_ms')->default(0);
            $table->unsignedBigInteger('white_clock_ms')->nullable();
            $table->unsignedBigInteger('black_clock_ms')->nullable();
            $table->timestamp('clock_updated_at')->nullable();
            $table->foreignId('draw_offered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('takeback_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('state_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['draw_offered_by']);
            $table->dropForeign(['takeback_requested_by']);
            $table->dropColumn(['time_control', 'initial_time_ms', 'increment_ms', 'white_clock_ms', 'black_clock_ms', 'clock_updated_at', 'draw_offered_by', 'takeback_requested_by', 'state_version']);
        });
    }
};
