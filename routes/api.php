<?php

use Illuminate\Support\Facades\Route;
use App\Modules\File\Http\Controllers\FileController;

Route::prefix('files')->group(function () {
    Route::get('/', [FileController::class, 'index']);
    Route::post('/', [FileController::class, 'store']);
    Route::get('/{id}', [FileController::class, 'show']);
    Route::get('/{id}/download', [FileController::class, 'download']);
    Route::delete('/{id}', [FileController::class, 'destroy']);
});
