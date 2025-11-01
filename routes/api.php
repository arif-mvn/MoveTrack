<?php

use App\Http\Controllers\Api\V1\Customer\ShipmentCustomerController;
use App\Http\Controllers\Api\V1\SourceController;
use App\Http\Controllers\Api\V1\TrackController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::group(['prefix' => 'shipment/v1/shipments'], function () {
    Route::get('/', [ShipmentCustomerController::class,'index']);
    Route::get('/{id}', [ShipmentCustomerController::class,'show']);
    Route::get('/track/{identifier}', [ShipmentCustomerController::class,'show']);
});

Route::group(['prefix' => 'source/v1/sources'], function () {
    Route::get('/', [SourceController::class,'index']);
    Route::get('/{id}', [SourceController::class,'show']);
});

Route::group(['prefix' => 'event/v1/events'], function () {
    Route::get('/', [ShipmentCustomerController::class,'index']);
    Route::get('/{id}', [ShipmentCustomerController::class,'show']);
});

Route::prefix('v1')->group(function () {
    Route::get('/shipments/{id}/timeline', [ShipmentCustomerController::class,'timeline']); //:toDo for admin group by leg and source

    // Tracking by identifier (PB..., YT..., last-mile TN, etc.)
    Route::get('/track/{value}', [TrackController::class, 'show']); // ?carrier_code=YunExpress&scope=last_mile //:toDo for customer group by leg
});
