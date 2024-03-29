<?php

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
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('homepage');
});
Route::get('importar','filecontroller@index');
Route::get('crear','crearController@index');
Route::post('process','fileController@guardar');
Route::post('insertar','fileController@save_mongo');
Route::get('pruebaxml','xmlController@index');//este es de pruebas  para xml luego borrar
Route::post('procesar_xml','fileController@procesar');
Route::get("prueba1",'xmlController@prueba');
/*Route::get('add','CarController@create');
Route::post('add','CarController@store');
Route::get('car','CarController@index');
Route::get('edit/{id}','CarController@edit');
Route::post('edit/{id}','CarController@update');
Route::delete('{id}','CarController@destroy');*/

?>
