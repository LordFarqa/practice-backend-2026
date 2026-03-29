<?php
// routes/api.php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Hotel\HotelsController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

// Публичные маршруты
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
})->name('api.health');

Route::get('/rooms/available', [BookingController::class, 'searchAvailable']);
Route::get('/rooms/{roomId}/schedule', [BookingController::class, 'roomSchedule']);
Route::get('/hotels/{hotelId}/reviews', [ReviewController::class, 'hotelReviews']);
Route::get('/hotels', [HotelsController::class, 'show']);
Route::get('/hotels/{id}', [HotelsController::class, 'showHotelById']);

// Защищенные маршруты
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::prefix('bookings')->group(function () {
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/my', [BookingController::class, 'myBookings']);
        Route::put('/{id}/cancel', [BookingController::class, 'cancelByUser']);
        Route::get('/completed', [BookingController::class, 'getCompletedBookings']);
    });
    
    Route::post('/reviews', [ReviewController::class, 'store']);
});

// Админские маршруты
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Users
    Route::get('/users', [AdminController::class, 'getAllUsers']);
    Route::get('/users/{id}', [AdminController::class, 'getUserById']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    
    // Hotels
    Route::get('/hotels', [AdminController::class, 'getAllHotels']);
    Route::get('/hotels/{id}', [AdminController::class, 'getHotelById']);
    Route::post('/hotels', [AdminController::class, 'createHotel']);
    Route::put('/hotels/{id}', [AdminController::class, 'updateHotel']);
    Route::delete('/hotels/{id}', [AdminController::class, 'deleteHotel']);
    
    // Rooms
    Route::get('/rooms', [AdminController::class, 'getAllRooms']);
    Route::get('/rooms/{id}', [AdminController::class, 'getRoomById']);
    Route::post('/rooms', [AdminController::class, 'createRoom']);
    Route::put('/rooms/{id}', [AdminController::class, 'updateRoom']);
    Route::delete('/rooms/{id}', [AdminController::class, 'deleteRoom']);
    
    // Room Classes
    Route::get('/room-classes', [AdminController::class, 'getAllRoomClasses']);
    Route::get('/room-classes/{id}', [AdminController::class, 'getRoomClassById']);
    Route::post('/room-classes', [AdminController::class, 'createRoomClass']);
    Route::put('/room-classes/{id}', [AdminController::class, 'updateRoomClass']);
    Route::delete('/room-classes/{id}', [AdminController::class, 'deleteRoomClass']);
    
    // Bookings
    Route::get('/bookings', [AdminController::class, 'getAllBookings']);
    Route::get('/bookings/{id}', [AdminController::class, 'getBookingById']);
    Route::delete('/bookings/{id}', [AdminController::class, 'deleteBooking']);
    
    // Stats
    Route::get('/stats', [AdminController::class, 'getStats']);
    Route::get('/stats/bookings', [AdminController::class, 'getBookingStats']);
});

// Fallback
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'error' => ['message' => 'API endpoint not found', 'code' => 'route_not_found']
    ], 404);
});