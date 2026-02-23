<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'welcome'])->name('landing');
Route::get('/book', [BookController::class, 'index'])->name('book.index');
Route::get('/book/{slug}', [BookController::class, 'show'])->name('book.show');
