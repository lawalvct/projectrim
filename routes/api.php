<?php

use App\Http\Controllers\Api\ApiDepartmentController;
use App\Http\Controllers\Api\ApiTagController;
use App\Http\Controllers\Api\ApiUserLookupController;
use Illuminate\Support\Facades\Route;

Route::get('/departments/{faculty}', [ApiDepartmentController::class, 'byFaculty']);
Route::get('/tags/search', [ApiTagController::class, 'search']);

Route::middleware('web')->group(function () {
    Route::get('/users/lookup', [ApiUserLookupController::class, 'lookup'])->middleware('auth');
});
