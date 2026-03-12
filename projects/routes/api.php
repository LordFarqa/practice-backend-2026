<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UsersController;

use App\Http\Controllers\Hotel\HotelsController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//admin
Route::get('/admin/user/{login}', [AdminController::class, 'show']);
Route::get('/admin/users/', [UsersController::class, 'show']);
Route::get('/admin/hotels/', [HotelsController::class, 'show']);
Route::get('/admin/hotel/{hotel_name}', [HotelsController::class, 'showByName']);
Route::get('/admin/hotel/{hotel_name}/rooms', [HotelsController::class, 'showRoomsByHotelName']);