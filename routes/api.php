<?php

use App\Http\Controllers\api\v1\AccountController;
use App\Http\Controllers\api\v1\AdminAccountController;
use App\Http\Controllers\api\v1\AdminMovementController;
use App\Http\Controllers\api\v1\AdminUserController;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\FavoriteController;
use App\Http\Controllers\api\v1\investmentsController;
use App\Http\Controllers\api\v1\MovementsController;
use App\Http\Controllers\api\v1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Rutas públicas de autenticación
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/ping', fn () => response()->json(['message' => 'pong']));
        // Agregamos ruta para el perfil del usuario autenticado
        Route::get('/profile', [ProfileController::class, 'show']);
        // Agregamos ruta para actualizar el perfil del usuario autenticado
        Route::put('/profile', [AuthController::class, 'update']);
        // Agregamos ruta para eliminar/dar de baja usuario registrado/logueado
        Route::delete('/profile', [AuthController::class, 'delete']);
        // Agregamos ruta para la cuenta del usuario autenticado
        Route::get('/account', [AccountController::class, 'show']);
        // Agregamos ruta para depostiar en cuenta del usuario autenticado
        Route::post('/deposits', [AccountController::class, 'store']);
        // agrega ruta para consultrar movimientos
        Route::get('/movements', [MovementsController::class, 'index']);
        // TRANSFERIR DINERO
        Route::post('/transfers', [MovementsController::class, 'transfer']);
        // GUARDAR CBU DE TERCEROS (favoritos)
        Route::post('/cbu/{cbu}/users/{idUser}', [FavoriteController::class, 'store']);
        // LISTAR CBU DE TERCEROS
        Route::get('/cbu/users/{idUser}', [FavoriteController::class, 'index']);
        // REMOVER CBU DE TERCEROS
        Route::delete('/cbu/{cbu}/users/{idUser}', [FavoriteController::class, 'destroy']);

        // Ruta simular plazo fijo
        Route::post('/investments/fixed-term/simulate', [investmentsController::class, 'store']);

        // Agregamos rutas para el administrador
        Route::prefix('admin')->middleware('administrador')->group(function () {

            Route::get('/ping', fn () => response()->json([
                'message' => 'Acceso administrativo autorizado',
            ]));

            Route::get('/movements', [AdminMovementController::class, 'index']);
            Route::post('/movements', [AdminMovementController::class, 'store']);
            Route::get('/movements/{movimiento}', [AdminMovementController::class, 'show']);
            Route::put('/movements/{movimiento}', [AdminMovementController::class, 'update']);
            Route::patch('/movements/{movimiento}', [AdminMovementController::class, 'update']);
            Route::delete('/movements/{movimiento}', [AdminMovementController::class, 'destroy']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::get('/users/{user}', [AdminUserController::class, 'show']);
            Route::put('/users/{user}', [AdminUserController::class, 'update']);
            Route::patch('/users/{user}', [AdminUserController::class, 'update']);
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

            Route::get('/accounts', [AdminAccountController::class, 'index']);
            Route::post('/accounts', [AdminAccountController::class, 'store']);
            Route::get('/accounts/{cuenta}', [AdminAccountController::class, 'show']);
            Route::put('/accounts/{cuenta}', [AdminAccountController::class, 'update']);
            Route::patch('/accounts/{cuenta}', [AdminAccountController::class, 'update']);
            Route::delete('/accounts/{cuenta}', [AdminAccountController::class, 'destroy']);
        });
    });
});
