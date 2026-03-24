<?php
// routes/api.php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Hotel\HotelsController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::get('/rooms/available', [BookingController::class, 'searchAvailable'])->name('api.rooms.available');
Route::get('/rooms/{roomId}/schedule', [BookingController::class, 'roomSchedule'])->name('api.rooms.schedule');
Route::get('/hotels/{hotelId}/reviews', [ReviewController::class, 'hotelReviews'])->name('api.hotels.reviews');
Route::get('/hotels', [HotelsController::class, 'show'])->name('api.hotels.index');
Route::get('/hotels/{id}', [HotelsController::class, 'showHotelById'])->name('api.hotels.show');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.user.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.user.logout');
    Route::prefix('bookings')->name('api.bookings.')->group(function () {
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::get('/my', [BookingController::class, 'myBookings'])->name('my');
        Route::put('/{id}/cancel', [BookingController::class, 'cancelByUser'])->name('cancel');
        Route::get('/completed', [BookingController::class, 'getCompletedBookings'])->name('completed');
    });
    Route::post('/reviews', [ReviewController::class, 'store'])->name('api.reviews.store');
});
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('api.admin.')->group(function () {
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::put('/{id}/cancel', [BookingController::class, 'cancelByAdmin'])->name('cancel');
        Route::get('/', [AdminController::class, 'getAllBookings'])->name('index');
        Route::get('/{id}', [AdminController::class, 'getBookingById'])->name('show');
        Route::delete('/{id}', [AdminController::class, 'deleteBooking'])->name('delete');
    });
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminController::class, 'getAllUsers'])->name('index');
        Route::get('/{id}', [AdminController::class, 'getUserById'])->name('show');
        Route::post('/', [AdminController::class, 'createUser'])->name('store');
        Route::put('/{id}', [AdminController::class, 'updateUser'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'deleteUser'])->name('delete');
    });
    Route::prefix('hotels')->name('hotels.')->group(function () {
        Route::get('/', [AdminController::class, 'getAllHotels'])->name('index');
        Route::get('/{id}', [AdminController::class, 'getHotelById'])->name('show');
        Route::post('/', [AdminController::class, 'createHotel'])->name('store');
        Route::put('/{id}', [AdminController::class, 'updateHotel'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'deleteHotel'])->name('delete');
    });
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', [AdminController::class, 'getAllRooms'])->name('index');
        Route::get('/{id}', [AdminController::class, 'getRoomById'])->name('show');
        Route::post('/', [AdminController::class, 'createRoom'])->name('store');
        Route::put('/{id}', [AdminController::class, 'updateRoom'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'deleteRoom'])->name('delete');
    });
    Route::prefix('room-classes')->name('room-classes.')->group(function () {
        Route::get('/', [AdminController::class, 'getAllRoomClasses'])->name('index');
        Route::get('/{id}', [AdminController::class, 'getRoomClassById'])->name('show');
        Route::post('/', [AdminController::class, 'createRoomClass'])->name('store');
        Route::put('/{id}', [AdminController::class, 'updateRoomClass'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'deleteRoomClass'])->name('delete');
    });
    
    Route::prefix('stats')->name('stats.')->group(function () {
        Route::get('/', [AdminController::class, 'getStats'])->name('index');
        Route::get('/bookings', [AdminController::class, 'getBookingStats'])->name('bookings');
    });
    
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => app()->environment()
    ]);
})->name('api.health');

Route::middleware('auth:sanctum')->get('/system/info', function () {
    return response()->json([
        'app_name' => config('app.name'),
        'app_version' => '1.0.0',
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'timezone' => config('app.timezone'),
        'locale' => app()->getLocale()
    ]);
})->name('api.system.info');

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'error' => [
            'message' => 'API endpoint not found',
            'code' => 'route_not_found',
            'status_code' => 404
        ]
    ], 404);
});