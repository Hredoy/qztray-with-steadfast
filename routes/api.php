<?php

use App\Http\Controllers\API\FraudCheckController;
use Illuminate\Support\Facades\Route;

Route::post('/fraud-check', [FraudCheckController::class, 'check']);
