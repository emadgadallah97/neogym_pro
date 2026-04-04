<?php

use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/*
| تسجيل الرسائل الواردة من خدمة Node (whatsapp-web.js) — يتطلب تفعيل WHATSAPP_INTERNAL_SECRET
| في .env وLARAVEL_INCOMING_WEBHOOK_URL في .env الخاص بـ Node.
*/
Route::post('/whatsapp/incoming', [WhatsAppController::class, 'incomingMessage']);
