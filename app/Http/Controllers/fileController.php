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

       if($xml != False){ //simple_xml_load retorna el obj xml o falso en caso de error; si el archivo fue recibido crrectamente
            if($tmp_tipo_arquetipo == "ACTION"){
                $concept = (string)$this->parser($xml)->xpath('//a:concept')[0];
                $busca_it = $this->parser($xml)->xpath('//a:definition');
                $busca_term_definition = $this->parser($xml)->xpath('//a:term_definitions');
                $def_attributes = count($busca_it[0]->attributes);

                if($def_attributes>=2){ //si tengo data y protocol
                    $def_1 = $busca_it[0]->attributes[0]->children; //PATHWAY
                    $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name;
                    $def_2 = $busca_it[0]->attributes[1]->children->attributes->children;//DESCRIPTION
                    $def_2_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
                    $def_3 = $busca_it[0]->attributes[2]->children->attributes->children; //PROTOCOL
                    $def_3_name =(string) $busca_it[0]->attributes[2]->rm_attribute_name;
                }
                //echo $def_1_name,$def_2_name,$def_3_name;

                //FOR PARA PATHWAY
                $array_pathway = [];
                //print_format($def_1);
                for ($i=0; $i < count($def_1); $i++) { 
                    $nombre = $busca_it[0]->attributes[0]->children[$i]->rm_type_name; //nombre todos los hijos de pathway,hasta children[$i] es cluster
                    $id = $busca_it[0]->attributes[0]->children[$i]->node_id; //id de hijos de pathway
                    $array_pathway[(string)$id] =(string) $nombre ; 
                }    
                //print_format($array_pathway);

                //FOR PARA DESCRIPTION
                $array_description= array();
                
                for ($h=0; $h < count($def_2); $h++) { 
                    $nombre_p = $busca_it[0]->attributes[1]->children->attributes->children[$h]->rm_type_name;
                    $id_p = $busca_it[0]->attributes[1]->children->attributes->children[$h]->node_id;
                    if ((string)$nombre_p == "CLUSTER") {
                        $cluster_description = $busca_it[0]->attributes[1]->children->attributes->children[$h];
                        $hijos_cluster_description = $this->recorre_hijos($cluster_description);
                        if($hijos_cluster_description == NULL){ //SI DEVUELVE NULL ES QUE NO TIENE MAS HIJOS
                            $array_description[(string)$id_p] = (string)$nombre_p;
                        }else{
                            $array_description[(string)$id_p] = $hijos_cluster_description;
                        }
                        
                    }else{
                        $array_description[(string)$id_p] = (string)$nombre_p;
                    }
                            
                }
                //FOR PARA PROTOCOL
                $array_protocol = array();
                for ($q=0; $q < count($def_3); $q++) { 
                    $nombre_protocol = $busca_it[0]->attributes[2]->children->attributes->children[$q]->rm_type_name;
                    $id_protocol = $busca_it[0]->attributes[2]->children->attributes->children[$q]->node_id;
                    //$array_protocol[(string)$id_protocol] =(string) $nombre_protocol;
                    if((string)$nombre_protocol == "CLUSTER"){
                        $cluster_protocol = $busca_it[0]->attributes[2]->children->attributes->children[$q];
                        $hijos_cluster_protocol = $this->recorre_hijos($cluster_protocol);
                        if($hijos_cluster_protocol == NULL){
                            $array_protocol[(string)$id_protocol] = (string) $nombre_protocol;
                        }else{
                            $array_protocol[(string)$id_protocol] = $hijos_cluster_protocol;
                        }
                    }else{
                        $array_protocol[(string)$id_protocol] =(string) $nombre_protocol;
                    }
                }

                list($aTrama,$aTrama1) = $this->buscar_item($busca_term_definition,'en',$concept); //obtengo los valores de id->elemento
                //arreglo aTrama que tiene los hijos del nodo root
                $primer_elemento_aTrama1 = reset($aTrama1);
                $aTrama1 = array();
                array_push($aTrama1,$primer_elemento_aTrama1);
                array_push($aTrama1,$def_1_name);
                array_push($aTrama1,$def_2_name);
                array_push($aTrama1,$def_3_name);
                //funcion match para renombrar los arreglos anteriores
                $array_pathway = $this->array_values_recursive($this->match($array_pathway,$aTrama));
                $array_protocol = $this->array_values_recursive($this->match($array_protocol,$aTrama));
                $array_description = $this->array_values_recursive($this->match($array_description,$aTrama));

                try {
                    $json_final = $this->crear_mind_jsmind_ACTION($aTrama1,$array_pathway,$array_protocol,$array_description,"right");
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
                    ],400);
                }

            }
            if($tmp_tipo_arquetipo == "EVALUATION"){
                $concept = (string)$this->parser($xml)->xpath('//a:concept')[0];
                $busca_it = $this->parser($xml)->xpath('//a:definition');
                $busca_term_definition = $this->parser($xml)->xpath('//a:term_definitions');
                $def_attributes = count($busca_it[0]->attributes);
                $def_1 = NULL;$def_2 = NULL;
                try {
                    if($def_attributes>=2){ //si tengo data y protocol
                        $def_1 = $busca_it[0]->attributes[0]->children->attributes->children;
                        $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name;
                        $def_2 = $busca_it[0]->attributes[1]->children->attributes->children;
                        $def_2_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
                    }
                } catch (Exception $e) {
                    $def_1 = NULL;
                    $def_2 = NULL;
                }
                
                //FOR DATA
                if($def_1 != NULL and $def_2 != NULL){
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
                    $tmp_pro= array();//AGREGUE ESTE FOR
                    for ($h=0; $h < count($def_2); $h++) { 
                        $nombre_p = (string)$busca_it[0]->attributes[1]->children->attributes->children[$h]->rm_type_name;
                        $id_p =(string) $busca_it[0]->attributes[1]->children->attributes->children[$h]->node_id;
                        if($nombre_p == "CLUSTER"){
                            $cluster_p = $busca_it[0]->attributes[1]->children->attributes->children[$h];
                            $hijos_c_p = $this->recorre_hijos($cluster_p);
                            $tmp_pro[(string)$id_p] = $hijos_c_p;
                        }else{
                            $tmp_pro[(string)$id_p] = $nombre_p;
                        }
                        
                    }

                }else{
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Arquetipo imposible de procesar',
                    ],400);
                }
                //CONEXION ID->ATTRIBUTE
                list($aTrama,$aTrama1) =$this->buscar_item($busca_term_definition,'en',$concept);

                $tmp_id = $this->match($tmp_id,$aTrama);
                $hijos_protocol = $this->match($tmp_pro,$aTrama);

                $nodo_root = $aTrama1[$concept];
                $aTrama1_padres = array();
                $aTrama2_hijos = $tmp_id;
                array_push($aTrama1_padres,$nodo_root);
                array_push($aTrama1_padres,$def_1_name);
                array_push($aTrama1_padres,$def_2_name);

                $aTrama2_hijos = $this->array_values_recursive($aTrama2_hijos);
                $aTrama3_hijos_c = $this->array_values_recursive($hijos_protocol);

                try {
                    $json_final = $this->crear_mind_jsmind($aTrama1_padres,$aTrama2_hijos,$aTrama3_hijos_c,"right");
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
                    ],400);
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
            ],404);
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
    function crear_array_hijos_jsmind($hijos,$id){
        $json_sender_data = array();
        foreach ($hijos as $keyq => $valueq) {
            $ind = $keyq+$id;
            $llave = (string) $ind;
            if(is_array($valueq)== FALSE){ //Si el nodo que estoy procesando no tiene mas hijos
                $valor = (string) $valueq;
                array_push($json_sender_data,json_encode(array('id'=>'"'.$llave.'"',"topic"=>$valor)));
            }else{
                $array_con_hijos = $valueq;
                $tmp_array_con_hijos = array();
                $padre_array_con_hijos = $array_con_hijos[0];//mas arriba explicado, siempre el padre estara en 0
                foreach ($array_con_hijos as $keyb =>$valueb) { //recorremos el arreglo para guardar sus hijos
                    $ind_2 = $keyb+$id+100;
                    $llave_kb = (string) $ind_2;
                    array_push($tmp_array_con_hijos,array('id'=>$llave_kb,"topic"=>$valueb));
                }
                array_push($json_sender_data,json_encode(array('id'=>$llave,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
            }

        }
        return $json_sender_data;
    }

    function crear_data_hijos_jsmind($padres,$hijos,$hijos_prot,$dir){ //funcion que crea los hijos de root (jsmind)-------------------
        //lo primero es crear el nodo root, padre de todos los demas nodos
        //NODO ROOT
        //$json_sender_data = array();
        $nodo_root = $padres[0];
        $string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        $string_f_p = (string) NULL;
        //DATA
        $json_sender_data = $this->crear_array_hijos_jsmind($hijos,100);
        //PROTOCOL
        $json_sender_protocol = $this->crear_array_hijos_jsmind($hijos_prot,600);

        $padres_split = array_chunk($padres, 1);//spliteamos nodoroot,data,protocol array(1=>array([0]=>data),
                                                                                        //2=>array([0]=>protocol))
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        foreach($padres_split as $keya => $valuea){ //recorrimos data,protocol
            $elemento = '"'.$valuea[0].'"'; //puede tomar data o protocol
            $id_padre = $keya+300;
            $id_padre_2 = $keya+400;
            if($valuea[0] == 'data'){
                $string_elem_padre .= '{"id":"'.$id_padre.'","topic":'.$elemento.',"direction":"'.$dir.'",';
                for ($i=0; $i < count($json_sender_data); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender_data[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender_data[$i];
                    }
                }
                $string_elem_padre .= $string_f."]},";
            }
            else{
                $string_elem_padre .= '{"id":"'.$id_padre_2.'","topic":'.$elemento.',"direction":"left",';
                for ($x=0; $x < count($json_sender_protocol); $x++) { 
                    if($x != 0){
                        $string_f_p .= ",".$json_sender_protocol[$x];
                    }else{
                        $string_f_p = '"children"'.":"."[".$json_sender_protocol[$x];
                    }
                }
                $string_elem_padre .= $string_f_p."]}";
            }
        }
        $string_nodo_root .= $string_elem_padre."]}";
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

    function crear_mind_jsmind($aData,$aData1,$aData2){ 
        $meta = $this->crear_meta_jsmind("archetype","importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind($aData,$aData1,$aData2,"right");
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }

    function obtener_hijos_cluster($aT,$padre){ //RECIBE UN PADRE Y RETORNA TODOS SUS HIJOS
        try { //Algunos cluster no tienen hijos, por eso intento contar los hijos
            //$hijos_cluster = $aT->attributes->children;//hijos de cluster--recibe items
            $hijos_cluster = $aT->children;
            $nro_hijos = count($hijos_cluster); //si los cuenta bien
            $nombre_hijos = (string)$aT->children->rm_attribute_name;
            //print_format($nombre_hijos);
        } catch (Exception $e) { //sino los puede contar
            $nro_hijos = FALSE; //no tiene hijos
            //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        if($nro_hijos != FALSE){
            //echo "paso arriba";
            $array_hijos = array();
            $array_hijos[(string)$padre->node_id] = (string)$padre->rm_type_name;//---------
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
        $hijos = NULL;
        try {  
            $tempo = $arg->attributes->rm_attribute_name;
            $new_arg = $arg->attributes;

            for ($n=0; $n < count($new_arg); $n++) { 
                if((string)$new_arg[$n]->rm_attribute_name == 'items'){
                    $hijos = $this->obtener_hijos_cluster($new_arg[$n],$arg);
                }
            }
        } catch (Exception $e) {
            $hijos = NULL;
        }
        if($hijos != NULL){ //Si tiene hijos y ahora debo verificar si dentro de sus hijos hay un cluster
            return $hijos;

        }else{
            return NULL;
        }
    }

    function match($tmp_id,$aTrama){ //AGREGUE ESTA FUNCION
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
        return $tmp_id;
    }

//
//
//
// FUNCIONES PARA ARQUETIPO DE TIPO ACTION
//
//
    function crear_mind_jsmind_ACTION($aData,$aData1,$aData2,$aData3,$dir){ 
        $meta = $this->crear_meta_jsmind("archetype","importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind_ACTION($aData,$aData1,$aData2,$aData3,$dir);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }


    function crear_array_hijos_jsmind_ACTION($hijos,$id){
        $json_sender_data = array();
        foreach ($hijos as $keyq => $valueq) {
            $ind = $keyq+$id;
            $llave = (string) $ind;
            if(is_array($valueq)== FALSE){ //Si el nodo que estoy procesando no tiene mas hijos
                $valor = (string) $valueq;
                array_push($json_sender_data,json_encode(array('id'=>'"'.$llave.'"',"topic"=>$valor)));
            }else{
                $array_con_hijos = $valueq;
                $tmp_array_con_hijos = array();
                $padre_array_con_hijos = $array_con_hijos[0];//mas arriba explicado, siempre el padre estara en 0
                foreach ($array_con_hijos as $keyb =>$valueb) { //recorremos el arreglo para guardar sus hijos
                    $ind_2 = $keyb+$id+100;
                    $llave_kb = (string) $ind_2;
                    array_push($tmp_array_con_hijos,array('id'=>$llave_kb,"topic"=>$valueb));
                }
                array_push($json_sender_data,json_encode(array('id'=>$llave,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
            }

        }
        return $json_sender_data;
    }

    function crear_data_hijos_jsmind_ACTION($padres,$array_pathway,$array_description,$array_protocol,$dir){ //funcion que crea los hijos de root (jsmind)-------------------
        //lo primero es crear el nodo root, padre de todos los demas nodos
        //NODO ROOT
        //$json_sender_data = array();
        $dir_2 = NULL;
        if($dir == "right"){
            $dir_2 = "left";
        }elseif($dir == "left"){
            $dir_2 = "right";
        }
        $nodo_root = $padres[0];
        $string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        $string_f_pathway = (string) NULL;
        $string_f_protocol = (string) NULL;
        //PATHWAY
        $id_pathway = 100;
        $json_sender_pathway = $this->crear_array_hijos_jsmind_ACTION($array_pathway,$id_pathway+1);
        //PROTOCOL
        $id_protocol = 200;
        $json_sender_protocol = $this->crear_array_hijos_jsmind_ACTION($array_protocol,$id_protocol+1);
        //DESCRIPTION
        $id_description = 300;
        $json_sender_description = $this->crear_array_hijos_jsmind_ACTION($array_description,$id_description+1);
        
        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final

        foreach ($padres_split as $keya => $valuea) {
            $elemento = '"'.$valuea[0].'"'; //puede tomar pathway,description, protocol
            //echo $valuea[0];
            if($valuea[0] == 'description'){
                $string_elem_padre .= ',{"id":"'.$id_description.'","topic":'.$elemento.',"direction":"'.$dir.'",';
                for ($i=0; $i < count($json_sender_description); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender_description[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender_description[$i];
                    }
                }
                $string_elem_padre .= $string_f."]}"; 
            }
            if($valuea[0] == 'ism_transition'){
                $string_elem_padre .= '{"id":"'.$id_pathway.'","topic":'.$elemento.',"direction":"'.$dir_2.'",';
                for ($y=0; $y < count($json_sender_pathway); $y++) { 
                    if($y != 0){
                        $string_f_pathway .= ",".$json_sender_pathway[$y];
                    }else{
                        $string_f_pathway = '"children"'.":"."[".$json_sender_pathway[$y];
                    }
                }
                $string_elem_padre .= $string_f_pathway."]}"; 
            }
            if($valuea[0] == 'protocol'){
                $string_elem_padre .= ',{"id":"'.$id_protocol.'","topic":'.$elemento.',"direction":"'.$dir_2.'",';
                for ($t=0; $t < count($json_sender_protocol); $t++) { 
                    if($t != 0){
                        $string_f_protocol .= ",".$json_sender_protocol[$t];
                    }else{
                        $string_f_protocol = '"children"'.":"."[".$json_sender_protocol[$t];
                    }
                }
                $string_elem_padre .= $string_f_protocol."]}"; 
                
            }
        }
        $string_nodo_root .= $string_elem_padre."]}";
        return $string_nodo_root;
    }
    
}

    
//EVALUATION,OBSERVation