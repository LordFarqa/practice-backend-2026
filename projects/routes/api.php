<?php
// routes/api.php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Hotel\HotelsController;
use Illuminate\Support\Facades\Route;

// Публичные маршруты
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Поиск (публичный)
Route::get('/rooms/available', [BookingController::class, 'searchAvailable']);
Route::get('/rooms/{roomId}/schedule', [BookingController::class, 'roomSchedule']);

// Отзывы (публичный)
Route::get('/hotels/{hotelId}/reviews', [ReviewController::class, 'hotelReviews']);

// Отели (публичный)
Route::get('/hotels', [HotelsController::class, 'show']);
Route::get('/hotels/{id}', [HotelsController::class, 'showHotelById']);

// Защищенные маршруты
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Бронирования
    Route::prefix('bookings')->group(function () {
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/my', [BookingController::class, 'myBookings']);
        Route::put('/{id}/cancel', [BookingController::class, 'cancelByUser']);
        Route::get('/completed', [BookingController::class, 'getCompletedBookings']);
    });
    

    Route::post('/reviews', [ReviewController::class, 'store']);
});


Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancelByAdmin']);
});