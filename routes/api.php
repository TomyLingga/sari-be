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
    //fr-user
    Route::get('request/index-mine', [App\Http\Controllers\Api\User\FormRequestController::class, 'index']);
    Route::post('request/add', [App\Http\Controllers\Api\User\FormRequestController::class, 'store']);
    Route::post('request/update/{id}', [App\Http\Controllers\Api\User\FormRequestController::class, 'edit']);
    Route::post('request/cancel/{id}', [App\Http\Controllers\Api\User\FormRequestController::class, 'cancel']);

    //fr-atasan
    Route::get('request/index-atasan', [App\Http\Controllers\Api\Atasan\FormRequestController::class, 'index']);
    Route::post('request/decline-atasan/{id}', [App\Http\Controllers\Api\Atasan\FormRequestController::class, 'decline']);
    Route::get('request/approve-atasan/{id}', [App\Http\Controllers\Api\Atasan\FormRequestController::class, 'approve']);

    //fr-dept
    Route::get('request/index-dept', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'index']);
    Route::post('request/decline-dept/{id}', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'decline']);
    Route::post('request/approve-dept/{id}', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'approve']);
    Route::post('request/execute/{id}', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'execute']);

    //cat
    Route::get('category/index-user-dept', [App\Http\Controllers\Api\Dept\CategoryController::class, 'index']);
    Route::get('category/index-dept', [App\Http\Controllers\Api\Dept\CategoryController::class, 'indexDept']);
    Route::get('category/index-by-dept/{id}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'indexByDept']);
    Route::get('category/get/{category}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'show']);
    Route::post('category/add', [App\Http\Controllers\Api\Dept\CategoryController::class, 'store']);
    Route::post('category/update/{category}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'update']);
});
Route::group(['middleware' => 'adminis'], function () {
    Route::get('request/index', [App\Http\Controllers\Api\User\FormRequestController::class, 'indexAll']);
    Route::get('request/get/{id}', [App\Http\Controllers\Api\User\FormRequestController::class, 'show']);
});

Route::fallback(function () {
    return response()->json(['code' => 404, 'message' => 'URL not Found'], 404);
});
