<?php
use Illuminate\Support\Facades\Route;
use MiamiOH\SnsHandler\Controllers\SnsMessageController;

Route::group(['prefix' => 'api', 'middleware' => 'api'], function() {
    Route::post('/sns/message', [SnsMessageController::class, 'handle']);
});
