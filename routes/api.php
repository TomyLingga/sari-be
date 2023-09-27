<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'user'], function () {
    //bast
    Route::get('form/index-by-user', [App\Http\Controllers\Api\User\FormRequestController::class, 'index']);
    // Route::post('form/add', [App\Http\Controllers\Api\User\FormController::class, 'store']);

    Route::get('category/index-by-dept', [App\Http\Controllers\Api\Dept\CategoryController::class, 'index']);
    Route::get('category/get/{category}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'show']);
    Route::post('category/add', [App\Http\Controllers\Api\Dept\CategoryController::class, 'store']);
    Route::post('category/update/{category}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'update']);
});

Route::fallback(function () {
    return response()->json(['code' => 404, 'message' => 'URL not Found'], 404);
});
