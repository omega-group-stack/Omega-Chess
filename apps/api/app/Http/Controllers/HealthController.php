<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function show()
    {
        $database = 'connected';

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $database = 'unavailable';
        }

        $ok = $database === 'connected';

        return response()->json([
            'service' => 'omega-chess-api',
            'status' => $ok ? 'ok' : 'degraded',
            'database' => $database,
            'cache' => config('cache.default'),
            'phase' => 1,
        ], $ok ? 200 : 503);
    }
}
