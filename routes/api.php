<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FinancialRecordController;
use App\Http\Controllers\Api\MedicalAppointmentController;
use App\Http\Controllers\Api\MedicalExamController;
use App\Http\Controllers\Api\WahaWebhookController;
use App\Http\Controllers\Api\WhatsAppAuthController;
use App\Http\Controllers\PlaidWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/plaid/webhook', [PlaidWebhookController::class, 'handleWebhook']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/whatsapp/verify', [WhatsAppAuthController::class, 'verify']);
Route::post('/auth/whatsapp/confirm', [WhatsAppAuthController::class, 'confirm']);

Route::post('/webhooks/waha', [WahaWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    Route::post('/attachments', [AttachmentController::class, 'store']);
    Route::post('/attachments/{attachment}/process', [AttachmentController::class, 'process']);

    Route::get('/transactions/recent', [FinancialRecordController::class, 'index']);
    Route::post('/transactions', [FinancialRecordController::class, 'store']);

    Route::get('/appointments', [MedicalAppointmentController::class, 'index']);
    Route::post('/appointments', [MedicalAppointmentController::class, 'store']);

    Route::get('/exams', [MedicalExamController::class, 'index']);
    Route::post('/exams', [MedicalExamController::class, 'store']);
});
