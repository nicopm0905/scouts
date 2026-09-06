<?php

use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

/*
| Web pública del grupo (sin autenticación).
| La portada vive en '/'; la galería ('/galeria') y la historia ('/historia')
| se registran en routes/features/photos.php y comparten el mismo layout público.
*/

Route::get('/', HomeController::class)->name('home');
