<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvaluationController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/v1/register', [AuthController::class, 'register']);
Route::post('/v1/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/logout', [AuthController::class, 'logout']);
    Route::post('/v1/upload', [EvaluationController::class, 'upload']);
    Route::post('/v1/evaluate', [EvaluationController::class, 'evaluate']);
    Route::get('/v1/status/{id}', [EvaluationController::class, 'status']);
    Route::get('/v1/result/{id}', [EvaluationController::class, 'result']);
});