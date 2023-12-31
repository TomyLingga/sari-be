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

    Route::get('request/{id}/pdf', [App\Http\Controllers\Api\User\FormRequestController::class, 'print']);
    //fr-user
    Route::get('request/index-mine', [App\Http\Controllers\Api\User\FormRequestController::class, 'index']);
    Route::post('request/add', [App\Http\Controllers\Api\User\FormRequestController::class, 'store']);
    Route::post('request/update/{id}', [App\Http\Controllers\Api\User\FormRequestController::class, 'edit']);
    Route::post('request/cancel/{id}', [App\Http\Controllers\Api\User\FormRequestController::class, 'cancel']);

    //problem-user
    Route::get('problem/index-mine', [App\Http\Controllers\Api\User\ProblemController::class, 'index']);
    Route::post('problem/add', [App\Http\Controllers\Api\User\ProblemController::class, 'store']);
    Route::post('problem/update/{id}', [App\Http\Controllers\Api\User\ProblemController::class, 'edit']);
    Route::post('problem/cancel/{id}', [App\Http\Controllers\Api\User\ProblemController::class, 'cancel']);

    //fr-atasan
    Route::get('request/index-atasan', [App\Http\Controllers\Api\Atasan\FormRequestController::class, 'index']);
    Route::post('request/decline-atasan/{id}', [App\Http\Controllers\Api\Atasan\FormRequestController::class, 'decline']);
    Route::get('request/approve-atasan/{id}', [App\Http\Controllers\Api\Atasan\FormRequestController::class, 'approve']);

    //fr-dept
    Route::get('request/index-dept', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'index']);
    Route::post('request/decline-dept/{id}', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'decline']);
    Route::post('request/approve-dept/{id}', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'approve']);
    Route::post('request/execute/{id}', [App\Http\Controllers\Api\Dept\FormRequestController::class, 'execute']);

    //problem-dept
    Route::get('problem/index-dept', [App\Http\Controllers\Api\Dept\ProblemController::class, 'index']);
    Route::post('problem/decline-dept/{id}', [App\Http\Controllers\Api\Dept\ProblemController::class, 'decline']);
    Route::get('problem/execute/{id}', [App\Http\Controllers\Api\Dept\ProblemController::class, 'execute']);
    Route::post('problem/done/{id}', [App\Http\Controllers\Api\Dept\ProblemController::class, 'done']);

    //categories
    Route::get('category/index-user-dept', [App\Http\Controllers\Api\Dept\CategoryController::class, 'index']);
    Route::get('category/index-dept', [App\Http\Controllers\Api\Dept\CategoryController::class, 'indexDept']);
    Route::get('category/index-by-dept/{id}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'indexByDept']);
    Route::get('category/get/{category}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'show']);
    Route::post('category/add', [App\Http\Controllers\Api\Dept\CategoryController::class, 'store']);
    Route::post('category/update/{category}', [App\Http\Controllers\Api\Dept\CategoryController::class, 'update']);

    Route::get('request/get/{id}', [App\Http\Controllers\Api\SA\FormRequestController::class, 'show']);
    Route::get('problem/get/{id}', [App\Http\Controllers\Api\Dept\ProblemController::class, 'show']);
});
Route::group(['middleware' => 'adminis'], function () {
    Route::get('request/index', [App\Http\Controllers\Api\SA\FormRequestController::class, 'indexAll']);
});

Route::fallback(function () {
    return response()->json(['code' => 404, 'message' => 'URL not Found'], 404);
});
