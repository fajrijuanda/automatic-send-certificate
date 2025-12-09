<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CertificateController;

Route::get('/', [CertificateController::class, 'index']);
Route::post('/send', [CertificateController::class, 'send'])->name('send.certificates');
