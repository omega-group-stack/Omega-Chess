<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('omega:about', function () {
    $this->info('Omega Chess Phase 1');
})->purpose('Show the Omega Chess application phase');
