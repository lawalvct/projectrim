<?php

use App\Http\Controllers\Api\ApiDepartmentController;
use App\Http\Controllers\Api\ApiSearchController;
use App\Http\Controllers\Api\ApiTagController;
use App\Http\Controllers\Api\ApiUserLookupController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::get('/departments/{faculty}', [ApiDepartmentController::class, 'byFaculty']);
Route::get('/tags/search', [ApiTagController::class, 'search']);

// Search autocomplete
Route::get('/search/autocomplete', [ApiSearchController::class, 'autocomplete']);
Route::get('/search/institutions', [ApiSearchController::class, 'institutions']);

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

Route::middleware('web')->group(function () {
    Route::get('/users/lookup', [ApiUserLookupController::class, 'lookup'])->middleware('auth');
});
