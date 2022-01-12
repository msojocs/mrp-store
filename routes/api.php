<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Upload2Controller;
use App\Http\Controllers\UploadController;
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

Route::post('upload/md5Check', [UploadController::class, 'md5Check']);
Route::post('upload/mrp', [UploadController::class, 'mrp']);
Route::post('upload/jar', [UploadController::class, 'jar']);

Route::post('uploa2d/md5Check', [Upload2Controller::class, 'md5Check']);
Route::post('upload2/mrp', [Upload2Controller::class, 'mrp']);
Route::post('upload2/jar', [Upload2Controller::class, 'jar']);


