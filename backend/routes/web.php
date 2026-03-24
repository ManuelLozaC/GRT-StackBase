<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'estado' => 'ok',
        'mensaje' => 'GRT StackBase API',
        'version' => '2026',
    ]);
});
