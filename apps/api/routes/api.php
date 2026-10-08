<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LobbyController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/lobby', [LobbyController::class, 'index']);
    Route::post('/lobby/seeks', [LobbyController::class, 'create']);
    Route::delete('/lobby/seeks/{seek}', [LobbyController::class, 'cancel']);
    Route::post('/lobby/seeks/{seek}/join', [LobbyController::class, 'join']);
    Route::post('/games', [GameController::class, 'store']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::get('/games/{game}/events', [GameController::class, 'events']);
    Route::post('/games/{game}/moves', [GameController::class, 'move']);
    Route::post('/games/{game}/resign', [GameController::class, 'resign']);
    Route::post('/games/{game}/draw/offer', [GameController::class, 'offerDraw']);
    Route::post('/games/{game}/draw/accept', [GameController::class, 'acceptDraw']);
    Route::post('/games/{game}/draw/decline', [GameController::class, 'declineDraw']);
    Route::post('/games/{game}/takeback/request', [GameController::class, 'requestTakeback']);
    Route::post('/games/{game}/takeback/accept', [GameController::class, 'acceptTakeback']);
    Route::post('/games/{game}/rematch', [GameController::class, 'rematch']);
    Route::get('/games/{game}/pgn', [GameController::class, 'pgn']);
});
