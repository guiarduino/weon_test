<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function whatsapp(Request $request, $number)
    {
        dispatch(new ProcessWebhookJob($number, $request->all()));

        return response()->json(['status' => 'ok'], 200);
    }
}
