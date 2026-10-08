<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_play_a_practice_game(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $created = $this->withHeaders($headers)->postJson('/api/games', ['mode' => 'practice']);
        $created->assertCreated();
        $id = $created->json('game.id');

        $this->withHeaders($headers)->postJson("/api/games/{$id}/moves", ['from' => 'e2', 'to' => 'e4'])
            ->assertOk()
            ->assertJsonPath('move.san', 'e4');

        $this->withHeaders($headers)->getJson("/api/games/{$id}")
            ->assertOk()
            ->assertJsonPath('game.turn', 'b')
            ->assertJsonCount(1, 'game.moves');
    }
}
