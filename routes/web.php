<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cart',[CartController::class,'index']);
Route::post('/invoice',[InvoiceController::class,'index']);

Route::post('/success',[InvoiceController::class,'success']);
Route::post('/fail',[InvoiceController::class,'fail']);
Route::post('/cancel',[InvoiceController::class,'cancel']);
Route::post('/ipn', [InvoiceController::class, 'ipn']);