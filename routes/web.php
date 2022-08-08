<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PruebasController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/**Rutas de pruebas */
Route::get('/', function () {
    return view('welcome');
});

Route::get('/pruebas/{nombre?}', function ($nombre = null) {
    $datos = '<h2> Primera prueba en laravel </h2>';
    $datos .= '<p>Nombre: '.$nombre.'</p>';
    return view('pruebas', [
        'texto' => $datos
    ]);
});
/**Ejemplos */
Route::get('/animales', [PruebasController::class, 'index']);
Route::get('/posts', [PruebasController::class, 'testOrm']);
/**Rutas de api  */
/**Metodos HttP
 * Get: Conseguir datos o recursos "obtener"
 * Post: Guardar datos o recursos o logiacas desde formularios "posterar envias"
 * Put: Actualizar datos o recursos 
 * DElete eliminar datos o recursos
 */
    /**Rutas de pruebas
     * Rutas usuarios */
    /*Route::get('/user/pruebas',[UserController::class, 'pruebas']);*/
    /**Categorias */
    /*Route::get('/category/pruebas',[CategoryController::class, 'pruebas']);*/
    /**Posts */
    /*Route::get('/post/pruebas',[PostController::class, 'pruebas']);*/
    /**Rutas para controlasor de usuarios */
    Route::post('/api/register',[UserController::class, 'register']);
    Route::post('/api/login',[UserController::class, 'login']);
    Route::put('/api/user/update',[UserController::class, 'update']);
    Route::post('/api/user/imageupload',[UserController::class, 'uploadImage'])->middleware('api.auth');
    Route::get('/api/user/avatar/{fileName}',[UserController::class, 'getImage']);
    Route::get('/api/user/profile/{id}',[UserController::class, 'profile']);

    /**Rutas para control de catagorias**/
    Route::resource('/api/category', CategoryController::class)->except([
        'create', 'edit' 
    ]);
    /**Rutas para controlador de entradas (Posts) */
    Route::resource('/api/post', PostController::class)->except([
        'create', 'edit'
    ]);
    Route::post('/api/post/image-upload/{post}', [PostController::class, 'uploadImage']);
    Route::get('/api/post/image/{post}', [PostController::class, 'getImage']);
    Route::get('/api/posts/cat/{category}', [PostController::class, 'getPostsByCategory']);
    Route::get('/api/posts/user/{user}', [PostController::class, 'getPostsByUser']);