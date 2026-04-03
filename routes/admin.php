<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\StaticDataController;

/*
|--------------------------------------------------------------------------
| Admin Dashboard API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/admin and require authentication.
| RBAC middleware enforces permissions on each endpoint.
|
*/

// Authentication required for all admin routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard & Statistics
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Admin Dashboard',
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'active_users' => \App\Models\User::where('is_active', true)->count(),
                'total_cars' => \App\Models\Car::count(),
                'total_expenses' => \App\Models\Expense::count(),
            ]
        ]);
    })->name('admin.dashboard');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:users.read'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::get('/users/export', [UserController::class, 'export']);
    });
    
    Route::middleware(['permission:users.create'])->group(function () {
        Route::post('/users', [UserController::class, 'store']);
    });
    
    Route::middleware(['permission:users.update'])->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    });
    
    Route::middleware(['permission:users.delete'])->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete']);
    });

    /*
    |--------------------------------------------------------------------------
    | Role & Permission Management
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:roles.read'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
    });
    
    Route::middleware(['permission:roles.create'])->group(function () {
        Route::post('/roles', [RoleController::class, 'store']);
    });
    
    Route::middleware(['permission:roles.update'])->group(function () {
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
    });
    
    Route::middleware(['permission:roles.delete'])->group(function () {
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Car Management
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:cars.read'])->group(function () {
        Route::get('/cars', [CarController::class, 'index']);
        Route::get('/cars/{car}', [CarController::class, 'show']);
        Route::get('/cars/export', [CarController::class, 'export']);
    });
    
    Route::middleware(['permission:cars.create'])->group(function () {
        Route::post('/cars', [CarController::class, 'store']);
    });
    
    Route::middleware(['permission:cars.update'])->group(function () {
        Route::put('/cars/{car}', [CarController::class, 'update']);
    });
    
    Route::middleware(['permission:cars.delete'])->group(function () {
        Route::delete('/cars/{car}', [CarController::class, 'destroy']);
        Route::post('/cars/bulk-delete', [CarController::class, 'bulkDelete']);
    });

    /*
    |--------------------------------------------------------------------------
    | Expense Management
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:expenses.read'])->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
        Route::get('/expenses/statistics', [ExpenseController::class, 'statistics']);
        Route::get('/expenses/export', [ExpenseController::class, 'export']);
    });
    
    Route::middleware(['permission:expenses.create'])->group(function () {
        Route::post('/expenses', [ExpenseController::class, 'store']);
    });
    
    Route::middleware(['permission:expenses.update'])->group(function () {
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    });
    
    Route::middleware(['permission:expenses.delete'])->group(function () {
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
        Route::post('/expenses/bulk-delete', [ExpenseController::class, 'bulkDelete']);
    });

    /*
    |--------------------------------------------------------------------------
    | Static Data Management (Brands, Models, etc.)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:static_data.read'])->group(function () {
        Route::get('/static/{model}', [StaticDataController::class, 'index'])
             ->where('model', 'brands|models|years|colors|shapes|fuel_types|maintenance_categories|expense_categories|cylinders');
        Route::get('/static/{model}/{id}', [StaticDataController::class, 'show'])
             ->where('model', 'brands|models|years|colors|shapes|fuel_types|maintenance_categories|expense_categories|cylinders');
    });
    
    Route::middleware(['permission:static_data.create'])->group(function () {
        Route::post('/static/{model}', [StaticDataController::class, 'store'])
             ->where('model', 'brands|models|years|colors|shapes|fuel_types|maintenance_categories|expense_categories|cylinders');
        Route::post('/static/{model}/import', [StaticDataController::class, 'import'])
             ->where('model', 'brands|models|years|colors|shapes|fuel_types|maintenance_categories|expense_categories|cylinders');
    });
    
    Route::middleware(['permission:static_data.update'])->group(function () {
        Route::put('/static/{model}/{id}', [StaticDataController::class, 'update'])
             ->where('model', 'brands|models|years|colors|shapes|fuel_types|maintenance_categories|expense_categories|cylinders');
    });
    
    Route::middleware(['permission:static_data.delete'])->group(function () {
        Route::delete('/static/{model}/{id}', [StaticDataController::class, 'destroy'])
             ->where('model', 'brands|models|years|colors|shapes|fuel_types|maintenance_categories|expense_categories|cylinders');
    });

    /*
    |--------------------------------------------------------------------------
    | Audit Logs
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:audit_logs.read'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
        Route::get('/audit-logs/statistics', [AuditLogController::class, 'statistics']);
        Route::get('/audit-logs/export', [AuditLogController::class, 'export']);
    });
});
