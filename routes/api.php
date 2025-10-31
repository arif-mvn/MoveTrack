<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\SourceController;
use App\Http\Controllers\Api\V1\TrackController;
//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');


Route::prefix('v1')->group(function () {
    // Generic resources
    Route::get('/sources',        [SourceController::class,  'index']);
    Route::get('/shipments',      [ShipmentController::class,'index']);
    Route::get('/shipments/{id}', [ShipmentController::class,'show']);
    Route::get('/shipments/{id}/timeline', [ShipmentController::class,'timeline']);

    // Tracking by identifier (PB..., YT..., last-mile TN, etc.)
    Route::get('/track/{value}', [TrackController::class, 'show']); // ?carrier_code=YunExpress&scope=last_mile
});
