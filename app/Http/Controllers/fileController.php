<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
class fileController extends Controller
{
    function index ()
    {
        return view('importarArquetipo');
        
    }

    // funcion para guardar el archivo xml
    function guardar (Request $request) {
        #$request->xmlfile->store('xml_imp');
        $validation = $request->validate(['xmlfile' => 'required|file|mimes:xml,adl|max:2048']);//2mb

        //obtenemos y validamos el archivo
        $file = $validation['xmlfile'];
        //extension
        $extension = $file->getClientOriginalExtension();
        //reconstruimos el nombre del archivo
        $filename = 'xml_archetype-' . time() . '.' . $extension;
        $filename2 = 'xml_archetype-' . time() . '.'. 'json';
        //lo guardamos en la carptea storage/app/xml_imports
        //$path = $file->storeAs('xml_imports',$filename); --------- 
        
        $xml = simplexml_load_file($file);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        //guardamos el json
        Storage::disk('json_files')->put($filename2, $json);
        Storage::disk('xml_imports')->put($filename,file_get_contents($file));
        #var_dump($path);
        if(Storage::disk('xml_imports')->exists($filename) && Storage::disk('json_files')->exists($filename2) ){  
            session(['estado_archivo'=>'archivo cargado con exito']);
            return response()->json([
                'status' => 'exito',
                'msg' => 'Archivo guardado con exito',
                'cod' => 201
            ], 201);
            
        }else{
            return response()->json([
                'status' => 'error',
                'msg' => 'Archivo no guardado',
                'cod' => 400
            ], 400);
        }

        //return redirect('importar')->with('success', 'Arquetipo cargado correctamente');
    }
}
