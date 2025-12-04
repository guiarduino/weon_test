<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// GET api/stops

Route::get('/webhook/whatsapp/{number}', [WebhookController::class, 'whatsapp']);