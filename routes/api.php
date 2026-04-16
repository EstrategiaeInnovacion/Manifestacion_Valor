<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VucemApiController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - VUCEM Gateway for C# Desktop Client
|--------------------------------------------------------------------------
|
| These endpoints are protected by Laravel Sanctum authentication.
| The C# client (VucemDownloader) will send Bearer tokens to authenticate.
|
*/

/*
|--------------------------------------------------------------------------
| Authentication - Public endpoints for C# Desktop App Login
|--------------------------------------------------------------------------
*/
Route::post('/auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected User Endpoints (require auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | E-Document Endpoints
    |--------------------------------------------------------------------------
    */
    Route::post('/vucem/consultar-edocument', [VucemApiController::class, 'consultarEdocument']);
    Route::post('/vucem/edocument/acuse', [VucemApiController::class, 'obtenerAcuseEdocument']);

    /*
    |--------------------------------------------------------------------------
    | COVE Endpoints
    |--------------------------------------------------------------------------
    */
    Route::post('/vucem/consultar-cove', [VucemApiController::class, 'consultarCove']);
    Route::post('/vucem/cove/acuse', [VucemApiController::class, 'obtenerAcuseCove']);

    /*
    |--------------------------------------------------------------------------
    | Manifestación de Valor (MVE) Endpoints
    |--------------------------------------------------------------------------
    */
    Route::post('/vucem/consultar-mve', [VucemApiController::class, 'consultarMve']);
    Route::post('/vucem/mve/acuse', [VucemApiController::class, 'obtenerAcuseMve']);

    /*
    |--------------------------------------------------------------------------
    | Pedimento Endpoints
    |--------------------------------------------------------------------------
    */
    Route::post('/vucem/consultar-pedimento', [VucemApiController::class, 'consultarPedimento']);
    Route::post('/vucem/listar-pedimentos', [VucemApiController::class, 'listarPedimentos']);
});