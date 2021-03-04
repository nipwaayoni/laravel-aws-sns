<?php
use Illuminate\Support\Facades\Route;
use MiamiOH\SnsHandler\Controllers\SnsMessageController;

Route::post('/sns/message', [SnsMessageController::class, 'handle']);