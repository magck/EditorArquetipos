<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class xmlController extends Controller
{
    public function index() //PRUEBAS BORORAR
    {
        return view('xml_pruebas');
    }
    public function prueba()
    {
        return view('prueba1');
    }
}
