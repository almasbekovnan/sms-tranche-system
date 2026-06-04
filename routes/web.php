<?php

use App\Http\Controllers\TrancheController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrancheController::class, 'index'])->name('tranche.index');
Route::post('/generate', [TrancheController::class, 'generate'])->name('tranche.generate');
