<?php

use App\Http\Middleware\SecretKey;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QrCodeController;

Route::post('qr/generate', [QrCodeController::class, 'generate'])->middleware(SecretKey::class);
Route::post('qr/scan', [QrCodeController::class, 'scan'])->middleware(SecretKey::class);
