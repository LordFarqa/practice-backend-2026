<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UsersController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//admin
Route::get('/admin/user/{login}', [AdminController::class, 'show']);
Route::get('/admin/users/', [UsersController::class, 'show']);
