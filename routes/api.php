<?php

use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/features',[FeaturesController::class,'api_index']);
Route::get('/features/{id}',[FeaturesController::class,'api_detail']);


Route::prefix('users')->group(function () {
    Route::post('/register',[UserController::class,'register']);
    Route::post('/login',[UserController::class,'Login']);
    Route::post('/register-with-email',[UserController::class,'RegisterWithEmail']);
    Route::post('/login-with-email',[UserController::class,'LoginWithEmail']);
});