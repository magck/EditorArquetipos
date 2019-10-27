<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\fileObject;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class fileController extends Controller
{
    function index (){
        return view('importarArquetipo');
    }
    
    function cargar(Request $request){
        $validation = $request->validate(['xmlfile' => 'required|file|mimes:xml,adl|max:2048']);//2mb
        $file = $validation['xmlfile'];
        $extension = $file->getClientOriginalExtension();

    }
    //funcion para procesar el xml y convertirlo a un tipo jsmind
    function procesar(Request $request){
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $validation = $request->validate(['archivo_xml' => 'required|file|mimes:xml,adl|max:2048']);//2mb
        $file = $validation['archivo_xml'];
        $xml = simplexml_load_file($file) or die("Error al cargar el xml");
        
        $tipos_conocidos = array('openEHR-EHR-ACTION','openEHR-EHR-EVALUATION','openEHR-EHR-OBSERVATION',
                "openEHR-EHR-INSTRUCTION","openEHR-EHR-ADMIN_ENTRY","openEHR-EHR-COMPOSITION"
            ,"openEHR-EHR-CLUSTER","openEHR-EHR-SECTION");
        $tipo_arquetipo_xml = (string)$this->parser($xml)->xpath('//a:archetype_id')[0]->value;
        $tipo_actual_arquetipo = explode(".",$tipo_arquetipo_xml)[0];

        foreach ($tipos_conocidos as $value) {
            if($value === $tipo_actual_arquetipo){
                $tmp_tipo_arquetipo = explode("-",$value)[2];
            }
        }
/*
        $json_mind = $this->crear_mind_jsmind($aData,$aData1);
        return response()->json([
            'padre' => $json_mind,
            'status' => 'good',
            'msg' => 'Archivo procesado con exito',
        ],201);
*/
       if($xml != False){ //simple_xml_load retorna el obj xml o falso en caso de error; si el archivo fue recibido crrectamente
            if($tmp_tipo_arquetipo == "ACTION"){
    

                }
            if($tmp_tipo_arquetipo == "EVALUATION"){
                $concept = (string)$this->parser($xml)->xpath('//a:concept')[0];
                $busca_it = $this->parser($xml)->xpath('//a:definition');
                $busca_term_definition = $this->parser($xml)->xpath('//a:term_definitions');
                $def_attributes = count($busca_it[0]->attributes);

                if($def_attributes>=2){ //si tengo data y protocol
                    $def_1 = $busca_it[0]->attributes[0]->children->attributes->children;
                    $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name;
                    $def_2 = $busca_it[0]->attributes[1]->children->attributes->children;
                    $def_2_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
                }
                //FOR DATA
                $tmp_id = [];
                for ($i=0; $i < count($def_1); $i++) { 
                    $nombre = (string)$busca_it[0]->attributes[0]->children->attributes->children[$i]->rm_type_name; //todos los hijos de data,hasta children[$i] es cluster
                    $id = $busca_it[0]->attributes[0]->children->attributes->children[$i]->node_id ; //id de hijos de data
                    if($nombre == "CLUSTER"){//si dentro de data encuentro un nodo llamado cluster
                        $cluster = $busca_it[0]->attributes[0]->children->attributes->children[$i];
                        $hijos_c = $this->recorre_hijos($cluster);
                        $tmp_id[(string)$id] = $hijos_c;
                    }else{
                        $tmp_id[(string)$id] = $nombre;
                    }
        
        
                }    
                //FOR PROTOCOL

                //CONEXION ID->ATTRIBUTE
                list($aTrama,$aTrama1) =$this->buscar_item($busca_term_definition,'en',$concept);
                foreach ($tmp_id as $key => $value) {
                    $nodo_id = array_key_exists($key,$aTrama);
                    if($nodo_id != FALSE){
                        $valor_actual_data = $aTrama[$key];
                        if(gettype($tmp_id[$key]) == 'array'){
                            foreach ($tmp_id[$key] as $llave => $valor) {
                                $nodo_id_2 = array_key_exists($llave,$aTrama);
                                if($nodo_id_2 != FALSE){
                                    $valor_actual_data_2 = $aTrama[$llave];
                                    $tmp_id[$key][$llave] = $valor_actual_data_2;
                                }
                            }
                        }else{
                            $tmp_id[$key] =  $aTrama[$key];
                        }
        
                    }
                }
                $nodo_root = $aTrama1[$concept];
                $aTrama1_padres = array();
                $aTrama2_hijos = $tmp_id;
                array_push($aTrama1_padres,$nodo_root);
                array_push($aTrama1_padres,$def_1_name);
                array_push($aTrama1_padres,$def_2_name);
                $aTrama2_hijos = $this->array_values_recursive($aTrama2_hijos);
                try {
                    $json_final = $this->crear_mind_jsmind($aTrama1_padres,$aTrama2_hijos,"right");
                } catch (Exception $e) {
                    $json_final = NULL;
                }
                if($json_final != NULL){
                    return response()->json([
                        'padre' => $json_final,
                        'status' => 'good',
                        'msg' => 'Archivo procesado con exito',
                        //'nombre_archetype'=>$aTrama1_padres[0],
                    ],201);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Archivo no encontrado',
                    ],201);
                }
            }
            if($tmp_tipo_arquetipo == "OBSERVATION"){
            
            }
            if($tmp_tipo_arquetipo == "INSTRUCTION"){
            
            }
            if($tmp_tipo_arquetipo == "ADMIN_ENTRY"){
            
            }
            if ($tmp_tipo_arquetipo == "COMPOSITION"){
            
            }
            if ($tmp_tipo_arquetipo == "CLUSTER"){
            
            }
            if ($tmp_tipo_arquetipo == "SECTION"){
                
            }
    }else{
            return response()->json([
                'status' => 'error',
                'msg' => 'Archivo no encontrado',
            ],201);
        }

    }


    // funcion para guardar el archivo xml en el storage
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
            return response()->json([
                'status' => 'exito',
                'msg' => 'Archivo guardado con exito',
                'cod' => '201',
            ], 201);
            
        }else{
            return response()->json([
                'status' => 'error',
                'msg' => 'Archivo no guardado',
                'cod' => '400',
            ], 400);
        }

    }

    //funcion para guardar en mongoDB
    function save_mongo(Request $request){
        $datos = new fileObject();
        $datos->nombre = $request['nombre'];
        $datos->archivo = $request['xmlsave'];
        $datos->extension = $request['extension'];
        $guardado = $datos->save();
        if($guardado){
            return response()->json([
                'status' => 'exito',
                'msg' => 'Archivo guardado en DB.',
                'cod' => '201',
            ], 201); 
        }else{
            return response()->json([
                'status' => 'error',
                'msg' => 'Archivo no guardado :/',
                'cod' => '400',
            ], 400);
        }
  
    }
    //
    //
    // FUNCIONES XML
    //
    //
    function crear_data_hijos_jsmind($padres,$hijos,$dir){ //funcion que crea los hijos de root (jsmind)
        //lo primero es crear el nodo root, padre de todos los demas nodos
        $json_sender = array();
        $nodo_root = $padres[0];
        $string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        foreach ($hijos as $keyq => $valueq) {
            $ind = $keyq+150;
            $llave = (string) $ind;
            if(is_array($valueq)== FALSE){ //Si el nodo que estoy procesando no tiene mas hijos
                $valor = (string) $valueq;
                array_push($json_sender,json_encode(array('id'=>'"'.$llave.'"',"topic"=>$valor)));
            }else{
                $array_con_hijos = $valueq;
                $tmp_array_con_hijos = array();
                $padre_array_con_hijos = $array_con_hijos[0];//mas arriba explicado, siempre el padre estara en 0
                foreach ($array_con_hijos as $keyb =>$valueb) { //recorremos el arreglo para guardar sus hijos
                    $ind_2 = $keyb+200;
                    $llave_kb = (string) $ind_2;
                    array_push($tmp_array_con_hijos,array('id'=>$llave_kb,"topic"=>$valueb));
                }
                array_push($json_sender,json_encode(array('id'=>$llave,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
            }

        }

        $padres_split = array_chunk($padres, 1);//spliteamos nodoroot,data,protocol array(1=>array([0]=>data),
                                                                                        //2=>array([0]=>protocol))
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        foreach($padres_split as $keya => $valuea){ //recorrimos data,protocol
            $elemento = '"'.$valuea[0].'"'; //puede tomar data o protocol
            $id_padre = $keya+100;
            if($valuea[0] == 'data'){
                $string_elem_padre .= '{"id":"'.$id_padre.'","topic":'.$elemento.',"direction":"'.$dir.'",';
                for ($i=0; $i < count($json_sender); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender[$i];
                    }
                }
                $string_elem_padre .= $string_f."]},";
            }
            else{
                $string_elem_padre .= '{"id":"'.$id_padre.'","topic":'.$elemento.',"direction":"'.$dir.'","children":""}]}';
            }
        }
        $string_nodo_root .= $string_elem_padre;
        //echo $string_nodo_root;
        return $string_nodo_root;
        
    }
    function crear_meta_jsmind($nombre,$autor,$version){
        $string_head = '"meta":{
            "name":"'.$nombre.'",
            "author":"'.$autor.'",
            "version":"'.$version.'"
        },';
        return $string_head;
    }
    function array_values_recursive($arr){
        $arr2=[];
        foreach ($arr as $key => $value)
        {
            if(is_array($value))
            {            
                $arr2[] = $this->array_values_recursive($value);
            }else{
                $arr2[] =  $value;
            }
        }
        return $arr2;
    }
    function crear_format_jsmind($formato){
        $string_format = '"format":"'.$formato.'",';
        return $string_format;
    }
    function buscar_item($padre,$idioma,$concepto){
        foreach($padre as $key=>$nodo)
        {
            switch ((string) $nodo['language']) {
                case $idioma:
                    $mykey = $key;
            }  
        }
        $tmp = $padre[$mykey]->items;
        $arreglo_items_code = array(); 
        $arreglo_item_tree = array();
        for ($i=0; $i < count($tmp); $i++) { 
            $descripcion = (string)$padre[$mykey]->items[$i]->items[1];
            $codigo = (string)$padre[$mykey]->items[$i]['code'];
            if($descripcion != "@ internal @"){ //Aqui hacemos un filtro de los items
                $item = (string)$padre[$mykey]->items[$i]->items[0];
                $arreglo_items_code[$codigo] = $item;
            }else{
                $item = (string)$padre[$mykey]->items[$i]->items[0];
                $arreglo_item_tree[$codigo] = $item;
            }
            if($codigo == $concepto){
                $item = (string)$padre[$mykey]->items[$i]->items[0];
                $arreglo_item_tree[$codigo] = $item;
            }
            foreach($arreglo_items_code as $clave => $valor){
                if($concepto == $clave){
                    unset($arreglo_items_code[$clave]);
                }
            }
        }
        return array($arreglo_items_code,$arreglo_item_tree);
    }
    function parser($xml){
        foreach($xml->getDocNamespaces() as $strPrefix => $strNamespace) {
            if(strlen($strPrefix)==0) {
                $strPrefix="a"; //Assign an arbitrary namespace prefix.
            }
            $xml->registerXPathNamespace($strPrefix,$strNamespace);
        }
        return $xml;
    }
    function crear_mind_jsmind($aData,$aData1){
        $meta = $this->crear_meta_jsmind($aData[0],"importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind($aData,$aData1,"right");
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }
    function obtener_hijos_cluster($aT){ //RECIBE UN PADRE Y RETORNA TODOS SUS HIJOS
        $hijos_cluster = $aT->attributes->children;//hijos de cluster
        try { //Algunos cluster no tienen hijos, por eso intento contar los hijos
            $nro_hijos = count($hijos_cluster); //si los cuenta bien
        } catch (\Exception $e) { //sino los puede contar
            $nro_hijos = FALSE; //no tiene hijos
            //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        if($nro_hijos != FALSE){
            $array_hijos = array();
            $array_hijos[(string)$aT->node_id] = (string)$aT->rm_type_name;
            //array_push($array_hijos,$aT->rm_type_name);
            for ($e=0; $e < $nro_hijos; $e++) { 
                $array_hijos[(string)$hijos_cluster[$e]->node_id] = (string)$hijos_cluster[$e]->rm_type_name;
            }
            return $array_hijos;
        }else{
            return NULL;
        }

    }

    function recorre_hijos($arg){
        $arreglo = array();
        try {
            $hijos = $this->obtener_hijos_cluster($arg);
        } catch (Exception $e) {
            $hijos = NULL;
        }
        if($hijos != NULL){ //Si tiene hijos y ahora debo verificar si dentro de sus hijos hay un cluster
            //$padre_siguientes = $arg->attribute->children;
            //recorre_hijos($padre_siguientes);
            return $hijos;
        }else{
            return NULL;
        }
    }
    
}
