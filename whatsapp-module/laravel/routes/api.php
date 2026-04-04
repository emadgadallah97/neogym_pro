<?php

use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




/*
| تسجيل الرسائل الواردة من خدمة Node (whatsapp-web.js) — يتطلب تفعيل WHATSAPP_INTERNAL_SECRET
| في .env وLARAVEL_INCOMING_WEBHOOK_URL في .env الخاص بـ Node.
*/
Route::post('/whatsapp/incoming', [WhatsAppController::class, 'incomingMessage']);
