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
        $validation = $request->validate(['archivo_xml' => 'required|file|mimes:xml|max:2048']);//2mb
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
                $def_1 = NULL;$def_2 = NULL;$def_3 = NULL;
                $def_1_name = NULL;$def_2_name = NULL;$def_3_name = NULL;
                
                try {
                    $def_1 = $busca_it[0]->attributes[0]->children; //PATHWAY
                    $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name;
                } catch (\Exception $e) {
                    $def_1 = NULL;
                    $def_1_name = NULL;
                }
                try {
                    $def_2 = $busca_it[0]->attributes[1]->children->attributes->children;//DESCRIPTION
                    $def_2_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
                } catch (\Exception $e) {
                    $def_2 = NULL;
                    $def_2_name = NULL;
                }
                try {
                    $def_3 = $busca_it[0]->attributes[2]->children->attributes->children; //PROTOCOL
                    $def_3_name =(string) $busca_it[0]->attributes[2]->rm_attribute_name;
                } catch (\Exception $e) {
                    $def_3 = NULL;
                    $def_3_name = NULL;
                }
                

                //PATHWAY
                if($def_1 != NULL and $def_1_name != NULL){
                    $array_pathway = $this->recorrer_xml_ACTION($busca_it[0]->attributes[0],count($def_1));
                }else{
                    $array_pathway = NULL;
                }

                //DESCRIPTION
                if($def_2 != NULL and $def_2_name != NULL){
                    $array_description= $this->recorrer_xml_ACTION($busca_it[0]->attributes[1]->children->attributes,count($def_2));
                }else{
                    $array_description = NULL;
                }
                
                //PROTOCOL
                if($def_3 != NULL and $def_3_name != NULL){
                    $array_protocol = $this->recorrer_xml_ACTION($busca_it[0]->attributes[2]->children->attributes,count($def_3));
                }else{
                    $array_protocol = NULL;
                }

                list($aTrama,$aTrama1) = $this->buscar_item_2($xml,'en',$concept); //obtengo los valores de id->elemento
                //arreglo aTrama que tiene los hijos del nodo root
                if($aTrama1 != NULL){
                    $primer_elemento_aTrama1 = reset($aTrama1);
                    $aTrama1 = array();
                    array_push($aTrama1,$primer_elemento_aTrama1);
                    array_push($aTrama1,$def_1_name);
                    array_push($aTrama1,$def_2_name);
                    array_push($aTrama1,$def_3_name);
                }
                //funcion match para renombrar los arreglos anteriores
                if($array_pathway != NULL){
                    $array_pathway = $this->array_values_recursive($this->match($array_pathway,$aTrama));
                }else{
                    $array_pathway = array();
                }
                if( $array_protocol != NULL){
                    $array_protocol = $this->array_values_recursive($this->match($array_protocol,$aTrama));
                }else{
                    $array_protocol = array();
                    /*return response()->json([
                        'status' => 'error',
                        'msg' => 'Arquetipo no contiene PROTOCOL',
                    ],400);*/
                }
                if($array_description != NULL){
                    $array_description = $this->array_values_recursive($this->match($array_description,$aTrama));
                }else{
                    $array_description = array();
                    /*return response()->json([
                        'status' => 'error',
                        'msg' => 'Arquetipo no contiene DESCRIPTION',
                    ],400);*/
                }
                $array_info = array($array_pathway,$array_description,$array_protocol);
                try {
                    $description_arquetipo = $this->crear_description_jsmind_DESCRIPTION($xml,$concept);
                    $attribution_arquetipo = $this->crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
                    //$aTrama1[0] tiene el nombre del arquetipo que se esta tratando
                    $nombre_Arq = explode("&&&",$aTrama1[0])[0];
                    $json_final = $this->crear_mind_jsmind_ACTION($aTrama1,$array_pathway,$array_protocol,$array_description,"right",$description_arquetipo,$nombre_Arq,$attribution_arquetipo);
                }catch (Exception $e) {
                    $json_final = NULL;
                    return response()->json([
                        'status' => 'error',
                        'msg' => $e->getMessage()
                    ],201);
                }
                if($json_final != NULL){
                    return response()->json([
                        'padre' => $json_final,
                        'status' => 'good',
                        'msg' => 'Archivo procesado con exito.',
                    ],201);   
                }else{
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Archivo XML imposible de procesar',
                    ],400);
                }

            }
            if($tmp_tipo_arquetipo == "EVALUATION"){
                $concept = (string)$this->parser($xml)->xpath('//a:concept')[0];
                $busca_it = $this->parser($xml)->xpath('//a:definition');
                $busca_term_definition = $this->parser($xml)->xpath('//a:term_definitions');
                $def_1 = NULL;$def_2 = NULL;
                try {
                    $def_1 = $busca_it[0]->attributes[0]->children->attributes->children;
                    $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name;
                } catch (\Exception $e) {
                    $def_1 = NULL;
                    $def_1_name = NULL;
                }
                try {
                    $def_2 = $busca_it[0]->attributes[1]->children->attributes->children;
                    $def_2_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
                } catch (\Exception $th) {
                    $def_2 = NULL;
                    $def_2_name = NULL;
                }
                //FOR DATA
                if($def_1 != NULL){
                    $tmp_id = array();
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
                }else{
                    $tmp_id = array();
                }
                if ($def_2 != NULL) {
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
                    $tmp_pro = array();
                }
                //CONEXION ID->ATTRIBUTE
                list($aTrama,$aTrama1) =$this->buscar_item_2($xml,'en',$concept);

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
                    $description_arquetipo = $this->crear_description_jsmind_DESCRIPTION($xml,$concept);
                    $attribution_arquetipo = $this->crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
                    //aTrama1_padres[0] tiene el nombre del arquetipo actual
                    $nombre_arq = explode("&&&",$aTrama1_padres[0])[0];
                    $json_final = $this->crear_mind_jsmind_EVALUATION($aTrama1_padres,$aTrama2_hijos,$aTrama3_hijos_c,"right",$description_arquetipo,$attribution_arquetipo,$nombre_arq);
                } catch (Exception $e) {
                    $json_final = NULL;
                }
                if($json_final != NULL){
                    return response()->json([
                        'padre' => $json_final,
                        'status' => 'good',
                        'msg' => 'Archivo procesado con exito',
                    ],201);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Archivo no encontrado',
                    ],400);
                }
            }
            if($tmp_tipo_arquetipo == "OBSERVATION"){
                $concept = (string)$this->parser($xml)->xpath('//a:concept')[0];
                $busca_it = $this->parser($xml)->xpath('//a:definition');
                $busca_term_definition = $this->parser($xml)->xpath('//a:term_definitions');
        
                //DATA
                try {
                    $def_1 = $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[0]; 
                    $short_def_1 = $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[0]->children->attributes->children;
                    $def_1_name =(string) $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[0]->rm_attribute_name;
                } catch (\Exception $t) {
                    $def_1 = NULL;
                    $short_def_1 = NULL;
                    $def_1_name = NULL;
                }
                //EVENTS
                try {
                    $def_eventos = $busca_it[0]->attributes[0]->children->attributes;//->children seran los eventos
                    $numero_eventos = $busca_it[0]->attributes[0]->children->attributes->children;
                    $nombre_eventos = "Events";
                } catch (\Exception $w) {
                    $def_eventos = NULL;
                    $numero_eventos = NULL;
                }
                //STATE o DATA ._.XD
                try {
                    $def_2 = $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1];
                    $short_def_2 = $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1]->children->attributes->children;
                    $def_2_name =(string) $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1]->rm_attribute_name;
                } catch (\Exception $e) {
                    $def_2 = NULL;
                    $short_def_2 = NULL;
                    $def_2_name = NULL;
                }
                //PROTOCOL
                try {
                    $def_3 = $busca_it[0]->attributes[1]->children->attributes->children;
                    $def_3_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
                } catch (\Exception $q) {
                    $def_3 = NULL;
                    $def_3_name = NULL;
                }
        
                //Si tengo Data
                if($def_1 != NULL and $short_def_1 != NULL and $def_1_name != NULL){
                    $array_data = $this->recorrer_xml_OBSERVATION($busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[0]->children->attributes,count($short_def_1));
                }else{
                    $array_data = array();
                }
        
                //Si tengo State o data xD
                if($def_2 != NULL and $short_def_2 != NULL and $def_2_name != NULL){
                    $array_state = $this->recorrer_xml_OBSERVATION($busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1]->children->attributes,count($short_def_2));
                }else{
                    $array_state = array();
                }
        
                //Si tengo protocol
                if($def_3 != NULL and $def_3_name != NULL){
                    $array_protocol = $this->recorrer_xml_OBSERVATION($busca_it[0]->attributes[1]->children->attributes,count($def_3));
                }else{
                    $array_protocol = array();
                }
                
                if($def_eventos != NULL and $numero_eventos != NULL){
                    $array_events = $this->recorrer_xml_OBSERVATION($busca_it[0]->attributes[0]->children->attributes,count($numero_eventos));
                }else{
                    $arrat_events = array();
                }
        
                list($aTrama,$aTrama1) = $this->buscar_item_2($xml,'en',$concept); //obtengo los valores de id->elemento
                //arreglo aTrama que tiene los hijos del nodo root
                $primer_elemento_aTrama1 = reset($aTrama1);
                $aTrama1 = array();
                array_push($aTrama1,$primer_elemento_aTrama1);
                array_push($aTrama1,$def_1_name);
                array_push($aTrama1,$nombre_eventos);
                array_push($aTrama1,$def_3_name);
                array_push($aTrama1,$def_2_name);
        
        
                //funcion match para renombrar los arreglos anteriores
                $array_state = $this->array_values_recursive($this->match($array_state,$aTrama));
                $array_data = $this->array_values_recursive($this->match($array_data,$aTrama));
                $array_protocol = $this->array_values_recursive($this->match($array_protocol,$aTrama));
                $array_events = $this->array_values_recursive($this->match($array_events,$aTrama));

                try {
                    $descripcion = $this->crear_description_jsmind_DESCRIPTION($xml,$concept);
                    $attributo = $this->crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
                    $nombre_a = explode("&&&",$primer_elemento_aTrama1)[0];
                    $json_final = $this->crear_mind_jsmind_OBSERVATION($aTrama1,$array_data,$array_protocol,$array_events,$array_state,$descripcion,$attributo,$nombre_a,"right");
                    
                } catch (Exception $e) {
                    $json_final = NULL;
                }
                if($json_final != NULL){
                    return response()->json([
                        'padre' => $json_final,
                        'status' => 'good',
                        'msg' => 'Archivo procesado con exito',
                    ],201);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Archivo no encontrado',
                    ],400);
                }
 
            }
            if($tmp_tipo_arquetipo == "INSTRUCTION"){
            }
            if($tmp_tipo_arquetipo == "ADMIN_ENTRY"){
            }
            if ($tmp_tipo_arquetipo == "COMPOSITION"){
            }
            if ($tmp_tipo_arquetipo == "CLUSTER"){
                $concept = (string)$this->parser($xml)->xpath('//a:concept')[0];
                $busca_it = $this->parser($xml)->xpath('//a:definition');
                $busca_term_definition = $this->parser($xml)->xpath('//a:term_definitions');
                $def_1 = NULL;$def_1_name = NULL;
                try {
                    $def_attributes = count($busca_it[0]->attributes);
                } catch (\Exception $e) {
                    echo "Arquetipo no cuenta con una etiqueta".$e->getMessage();
                    $def_attributes = FALSE;
                }
                
                if($def_attributes>=1 and $def_attributes!=FALSE){ //si tengo items
                    $def_1 = $busca_it[0]->attributes[0]; //items para procesar en xml
                    $short_def_1 = $busca_it[0]->attributes[0]->children;
                    $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name; //items
                }else{
                    $def_1 = array();
                    $short_def_1 = array();
                    $def_1_name = (string) NULL;
                    /*return response()->json([
                        'status' => 'error',
                        'msg' => 'Archivo Imposible de procesar',
                    ],400);*/
                }
                if($def_1 != NULL and $def_1_name != NULL){
                    $array_items = $this->recorrer_xml_CLUSTER($def_1,count($short_def_1));
                }
                
                list($aTrama,$aTrama1) = $this->buscar_item_2($xml,'en',$concept); //obtengo los valores de id->elemento
                //arreglo aTrama que tiene los hijos del nodo root
                $primer_elemento_aTrama1 = reset($aTrama1);
                $aTrama1 = array();
                array_push($aTrama1,$primer_elemento_aTrama1);
                array_push($aTrama1,$def_1_name);
                $aTrama_datatypes = $aTrama;
        
                //funcion match para renombrar los arreglos anteriores
                $array_items = $this->array_values_recursive($this->match($array_items,$aTrama));
        
                try {
                    $description_xml = $this->crear_description_jsmind_DESCRIPTION($xml,$concept);
                    $attributo_xml = $this->crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
                    $json_final = $this->crear_mind_jsmind_CLUSTER($aTrama1,$array_items,"left",$aTrama1[0],$description_xml,$attributo_xml);
                } catch (Exception $e) {
                    $json_final = NULL;
                }
                if($json_final != NULL){
                    return response()->json([
                        'padre' => $json_final,
                        'status' => 'good',
                        'msg' => 'Archivo procesado con exito',
                    ],201);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Archivo corrupto',
                    ],400);
                }
        
            }
            if ($tmp_tipo_arquetipo == "SECTION"){
            }
    }else{
            return response()->json([
                'status' => 'error',
                'msg' => 'Archivo no recibido correctamente.',
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
    
    //FUNCION QUE EXTRAE DESCRIPTION METADATA DE LOS ARQUETIPOS
    //DEVUELVE UN STRING TIPIFICADO JSON PARA AGREGARLO AL JSON FINAL
    function crear_description_jsmind_DESCRIPTION($xml,$concept){
        $details = $this->parser($xml)->xpath('//a:details');
        foreach ($details as $elemento => $valor) {
            if((string)$valor->language->code_string == "en"){
                $proposito = array("purpose",(string)$valor->purpose);
                $use = array("use",(string)$valor->use);
                
                $string_keywords = (string) NULL;
                for ($r=0; $r < count($valor->keywords); $r++) { 
                    if($r != 0){
                        $string_keywords .= ",".$valor->keywords[$r];
                    }else{
                        $string_keywords .= $valor->keywords[$r];
                    }
                }

                $keywords = array("keywords",$string_keywords);
                $misuse = array("misuse",(string)$valor->misuse);
                $copyright = array("copyright",(string)$valor->copyright); //no usado
                $ref_for = $this->parser($xml)->xpath('//a:other_details');
                $referencia = array("references",NULL);
                try {
                    for ($t=0; $t < count($ref_for); $t++) { 
                        if ((string)$ref_for[$t]->attributes() == 'references') {
                            $referencia = array("references",(string)$ref_for[$t][0]);
                        }
                    }
                } catch (\Exception $e) {
                    $referencia = array("references",NULL);
                }
                $conc = $this->parser($xml)->xpath('//a:ontology');
                $concept_description = array("Concept description",NULL);;
                try {
                    foreach ($conc[0]->term_definitions as $ll => $vv) {
                        if((string)$vv->attributes()->language == "en"){
                            if($vv->items['code'] == $concept){
                                try {
                                    $concept_description = array("Concept description",(string)$vv->items->items[1]);
                                } catch (\Exception $r) {
                                    $concept_description = array("Concept description",NULL);
                                }
                            }
                            
                        }
                        
                    }
                } catch (\Exception $e) {
                    $concept_description = array("Concept description",NULL);
                }
                $array_hijos_description = array();
                array_push($array_hijos_description,$concept_description);
                array_push($array_hijos_description,$proposito);
                array_push($array_hijos_description,$use);
                array_push($array_hijos_description,$keywords);
                array_push($array_hijos_description,$referencia);

                try {
                    $ide_description = 200;
                    $hijos_description = $this->crear_array_hijos_jsmind_for_Attibution_Description($array_hijos_description,$ide_description+1);
                    
                    $string_description = '{"id":"'.$ide_description.'","topic":"Description","direction":"right","children":[';
                    for ($e=0; $e < count($hijos_description); $e++) {
                        if($e != 0){
                            $string_description.= ",".$hijos_description[$e];
                        }else{
                            $string_description.= $hijos_description[$e];
                        }
                        
                    }
                    $string_description .= "]}"; //ESTE ES EL STRING FINAL 
                } catch (\Exception $e) {
                    return NULL;
                }
                return $string_description;
            }
        }
    }
    function crear_attribution_jsmind_ATTRIBUTION($xml,$concept){
        $archetype_id = array("Archetype ID",NULL);
        $other_Identification = array("Other Identification",NULL);
        $original_author =array("Original Author",NULL);
        $current_custodian = array("Current Custodian",NULL);
        $other_contributors = array("Other Contributos",NULL);
        $translators = array("Translators",NULL);
        $licencing = array("Licencing",NULL);
        try {
            $ruta_archetype = (string)$this->parser($xml)->xpath('//a:archetype_id')[0]->value;
            $archetype_id = array("Archetype ID:",$ruta_archetype);
        } catch (\Exception $e) {
            $archetype_id = array("Archetype ID:",NULL);
        }
        try {
            $M_V_ID = "Major Version ID:".(string) $this->parser($xml)->xpath('//a:uid')[0]->value;
            $ref_other_d =$this->parser($xml)->xpath('//a:other_details');
            $other_details_MDI = (string) NULL;
            $other_details_build_uid = (string) NULL;
            try {
                for ($t=0; $t < count($ref_other_d); $t++) { 
                    if ((string)$ref_other_d[$t]->attributes() == 'MD5-CAM-1.0.1') {
                        $other_details_MDI = "Canonical MD5 Hash:".(string)$ref_other_d[$t][0];
                    }
                    if ((string)$ref_other_d[$t]->attributes() == 'build_uid') {
                        $other_details_build_uid = "Build Uid:".(string)$ref_other_d[$t][0];
                    }
                }
                $strin_final_identification = $M_V_ID.",".$other_details_MDI.",".$other_details_build_uid;
                $other_Identification = array("Other Identification:",$strin_final_identification);  
            } catch (\Exception $e) {
                $other_details_MDI = (string) NULL;
                $other_details_build_uid = (string) NULL;
            }
        } catch (\Exception $e) {
            $other_Identification = array("Other Identification",NULL);
        }
        try {
            $ref_original_autor = $this->parser($xml)->xpath('//a:original_author');
            $original_autor_date = (string) NULL;
            $original_autor_name = (string) NULL;
            $original_autor_organisation = (string) NULL;
            $original_autor_email = (string) NULL;
            try {
                for ($h=0; $h < count($ref_original_autor); $h++) { 
                    if((string)$ref_original_autor[$h]->attributes() == "date"){
                        $original_autor_date = "Date original authored:".(string)$ref_original_autor[$h][0];
                    }
                    if((string)$ref_original_autor[$h]->attributes() == "name"){
                        $original_autor_name = "Author name:".(string)$ref_original_autor[$h][0];
                    }
                    if((string)$ref_original_autor[$h]->attributes() == "organisation"){
                        $original_autor_organisation = "Organisation:".(string)$ref_original_autor[$h][0];
                    }
                    if((string)$ref_original_autor[$h]->attributes() == "email"){
                        $original_autor_email = "Email:".(string)$ref_original_autor[$h][0];
                    }
                }
                $string_final_original_author = $original_autor_name.",".$original_autor_organisation.",".$original_autor_email.",".$original_autor_date;
                $original_author =array("Original Author",$string_final_original_author);
            } catch (\Exception $e) {
                $original_author =array("Original Author",NULL);
            }
        } catch (\Exception $e) {
            $original_author =array("Original Author",NULL);
        }
        try {
            $ref_custodian = $this->parser($xml)->xpath('//a:other_details');
            $custodian_namespace = (string) NULL;
            $custodian_organisation = (string) NULL;
            $custodian_contact = (string) NULL;
            try {
                for ($p=0; $p < count($ref_custodian); $p++) { 
                    if((string)$ref_custodian[$p]->attributes() == "custodian_organisation"){
                        $custodian_organisation = "Custodian Organisation:".(string)$ref_custodian[$p][0];
                    }
                    if((string)$ref_custodian[$p]->attributes() == "custodian_namespace"){
                        $custodian_namespace = "Custodian Namespace:".(string)$ref_custodian[$p][0];
                    }
                    if((string)$ref_custodian[$p]->attributes() == "current_contact"){
                        $custodian_contact = "Custodian contact:".(string)$ref_custodian[$p][0];
                    }
                }
                $string_final_custodian= $custodian_organisation.",".$custodian_namespace.",".$custodian_contact.",";
                $current_custodian = array("Current Custodian",$string_final_custodian);
            } catch (\Exception $e) {
                $current_custodian = array("Current Custodian",NULL);
            }
        } catch (\Exception $h) {
            $current_custodian = array("Current Custodian",NULL);
        }
        try {
            $ref_contri = $this->parser($xml)->xpath('//a:other_contributors');
            $string_contri = (string) NULL;
            try {
                for ($n=0; $n < count($ref_contri); $n++) { 
                   if($n >0){
                    $string_contri .= "&& ".$ref_contri[$n]." ";
                   }else{
                    $string_contri .= $ref_contri[$n]." ";
                   }
                }
                $other_contributors = array("Other Contributos",$string_contri);
            } catch (\Exception $e) {
                $other_contributors = array("Other Contributos",NULL);
            }
        } catch (\Exception $u) {
            $other_contributors = array("Other Contributos",NULL);
        }
        try {
            $translations = $this->parser($xml)->xpath("//a:translations");
            $sting_autor_translations = (string) NULL;
            for ($s=0; $s < count($translations); $s++) { 
                $lenguage = $translations[$s]->language->code_string;
                $athor_for = $translations[$s]->author;
                for ($g=0; $g < count($athor_for); $g++) { 
                    if ($g > 0) {
                        $sting_autor_translations .= ",".$athor_for[$g]." ";
                    }else{
                        $sting_autor_translations .= "(".$lenguage."): ".$athor_for[$g];
                    }
                }
            }
            $translators = array("Translators",$sting_autor_translations);
        } catch (\Exception $e) {
            $translators = array("Translators",NULL);
        }
        try {
            $licencia = $this->parser($xml)->xpath("//a:details");
            $lic = $this->parser($xml)->xpath("//a:other_details");
            for ($m=0; $m < count($licencia); $m++) { 
                if((string)$licencia[$m]->language->code_string == "en"){
                    $copy = "Copyright: ".(string)$licencia[$m]->copyright." ";
                }
            }
            for ($w=0; $w < count($lic); $w++) { 
                if((string)$lic[$w]->attributes() == "licence"){
                    $licencia_arquetipo = "Licence: ".(string)$lic[$w][0];
                }
            }
            $string_final_licence = $copy.$licencia_arquetipo;
            $licencing = array("Licencing",$string_final_licence);
        } catch (\Exception $u) {
            $licencing = array("Licencing",NULL);
        }
        $array_hijos_attibution = array();
        array_push($array_hijos_attibution,$archetype_id);
        array_push($array_hijos_attibution,$other_Identification);
        array_push($array_hijos_attibution,$original_author);
        array_push($array_hijos_attibution,$current_custodian);
        array_push($array_hijos_attibution,$other_contributors);
        array_push($array_hijos_attibution,$translators);
        array_push($array_hijos_attibution,$licencing);
        try {
            $id_attribution = 10000;
            $hijos_attribution = $this->crear_array_hijos_jsmind_for_Attibution_Description($array_hijos_attibution,$id_attribution+1);
            $string_attribution = '{"id":"'.$id_attribution.'","topic":"Attribution","direction":"right","children":[';
            for ($t=0; $t < count($hijos_attribution); $t++) {
                if($t != 0){
                    $string_attribution.= ",".$hijos_attribution[$t];
                }else{
                    $string_attribution.= $hijos_attribution[$t];
                }
            }
            $string_attribution .= "]}"; //ESTE ES EL STRING FINAL 
        } catch (\Exception $e) {
            return NULL;
        }
        return $string_attribution;
    }
    function crear_array_hijos_jsmind_for_Attibution_Description($hijos,$id){
        $json_sender_data = array();
        $ind_padre = $id;
        if(is_array($hijos) == TRUE and count($hijos)>=1){
            for ($w=0; $w < count($hijos); $w++) {
                if($w != 0){
                    $ind_padre = $ind_padre+50;
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        $padre_array_con_hijos = $array_con_hijos[0];
                        for ($q=0; $q < count($array_con_hijos); $q++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $q;
                            if ($q != 0) {
                                array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"topic"=>$array_con_hijos[$q]));
                            }
                        }
                        array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }
                        //array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$hijos[$w])));
                    }
                }else{
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        $padre_array_con_hijos = $array_con_hijos[0];
                        for ($u=0; $u < count($array_con_hijos); $u++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $u;
                            if ($u != 0) {
                                $valor_d = explode(",",$array_con_hijos[$u]);
                                //print_format($array_con_hijos);
                                array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"topic"=>$array_con_hijos[$u]));
                            }
                        }
                        array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }
                        //array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$hijos[$w])));
                    }
                } 
    
            }
            return $json_sender_data; 
        }else{
            return $json_sender_data; 
        }
    }
    //FUNCION QUE JUNTA LAS 3 FUNCIONES Y RETORNA EL JSON FINAL para jsMind
    function crear_mind_jsmind($aData,$aData1,$aData2){ 
        $meta = $this->crear_meta_jsmind("archetype","importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind($aData,$aData1,$aData2,"right");
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }
    //funcion que crea un simple json para el jsMind con la metadata del arquetipo
    function crear_meta_jsmind($nombre,$autor,$version){
        $string_head = '"meta":{
            "name":"'.$nombre.'",
            "author":"'.$autor.'",
            "version":"'.$version.'"
        },';
        return $string_head;
    }
    //FUNCION PARA CREAR UN SIMPLE JSON PARA EL APARTADO FORMAT DEL JSMIND
    function crear_format_jsmind($formato){
        $string_format = '"format":"'.$formato.'",';
        return $string_format;
    }
    //FUNCION QUE RECORRE ARREGLOS Y LOS DEVUELVE CON INDICES ORDENADOS
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

    //FUNCION QUE BUSCA EN <ontology> del DEL ARQUETIPO Y RETORNA 2 ARREGLOS
    //primer arreglo : [code] = Text &&& Description &&& Comment &&& Source (todo junto, un string)
    //ejemplo : [at0001] : Inyeccion &&& usada para prevenir... &&& prefiero usarla &&& openEHR
    //segundo arreglo : [code] = Nombre nodo root &&& description &&& comment
    //ejemplo : [at0004] : Prevenir sarampion &&& arquetipo para...
    function buscar_item_2($xml,$lenguage_default,$concept_xml){
        $arreglo_code_items = array();
        $arreglo_nodo_root = array();
        $term_definition = $this->parser($xml)->xpath('//a:term_definitions');
        for ($w=0; $w < count($term_definition); $w++) { 
            if((string)$term_definition[$w]->attributes() == $lenguage_default){
                $items_recorrer = $term_definition[$w]->items;
                for ($q=0; $q < count($items_recorrer); $q++) {
                    $items_de_id = $this->array_to_string($items_recorrer[$q]);
                    if ((string)$items_recorrer[$q]->attributes() != $concept_xml) {
                        $arreglo_code_items[(string)$items_recorrer[$q]->attributes()] = $items_de_id;
                    }else{
                        $arreglo_nodo_root[(string)$items_recorrer[$q]->attributes()] = $items_de_id;
                    }
                } 
            }
        }
        return array($arreglo_code_items,$arreglo_nodo_root);
    }
    //FUNCION QUE FACILITA LA BUSQUEDA DE XML
    function parser($xml){
        foreach($xml->getDocNamespaces() as $strPrefix => $strNamespace) {
            if(strlen($strPrefix)==0) {
                $strPrefix="a"; //Assign an arbitrary namespace prefix.
            }
            $xml->registerXPathNamespace($strPrefix,$strNamespace);
        }
        return $xml;
    }
    //FUNCION QUE RECORRE LOS HIJOS DE UN NODO XML
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
    //RECORRE LOS HIJOS DE UN ELEMENTO TIPO CLUSTER
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

    //FUNCION QUE HACE EL MATCH CON LOS ID DE LO PROCESADO EN PRIMERA INSTANCIA EN EL XML
    //HACE USO DE UNO DE LOS ARREGLOS QUE RETORNA BUSCAR_ITEM()
    // retorna un arreglo con los elementos renombrados
    //ejemplo : [at0004] = Inyecion penicilina ### tipo de dato
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
                            $tmp_id[$key][$llave] = $tmp_id[$key][$llave]."####".$valor_actual_data_2;
                        }
                    }
                }else{
                    $tmp_id[$key] = $tmp_id[$key]."####".$aTrama[$key];
                }

            }
        }
        return $tmp_id;
    }
    //recibe un XML y guarda todos los nodos hijos (usada para la funcion buscar_item_2())
    function array_to_string($xml){
        $xml = $xml->items;
        $string_a_retornar = (string) NULL;
        for ($i=0; $i < count($xml); $i++) {
            if ($i >0) {
                $string_a_retornar .= "&&&".$xml[$i];
            }else{
                $string_a_retornar .= $xml[$i];
            }
        }
        return $string_a_retornar;
    }

//
//
//
// FUNCIONES PARA ARQUETIPO DE TIPO ACTION
//
//
    // Crea el Mind principal de los arquetipos action
    function crear_mind_jsmind_ACTION($aData,$aData1,$aData2,$aData3,$dir,$description_arquetipo,$nombre,$attribution_arquetipo){ 
        $meta = $this->crear_meta_jsmind($nombre,"importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind_ACTION($aData,$aData1,$aData2,$aData3,$dir,$description_arquetipo,$attribution_arquetipo);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }
    //recibe los padres del arquetipo y junta los hijos con respecto a su padre especifico
    function crear_data_hijos_jsmind_ACTION($padres,$array_pathway,$array_protocol,$array_description,$dir,$description_arquetipo,$attribution_arquetipo){ //funcion que crea los hijos de root (jsmind)-------------------
        $dir_2 = NULL;
        if($dir == "right"){
            $dir_2 = "left";
        }elseif($dir == "left"){
            $dir_2 = "right";
        }
        //lo primero es crear el nodo root, padre de todos los demas nodos
        //NODO ROOT
        $nodo_root = $padres[0];
        $padre_explode = explode("&&&",$nodo_root);
        if(count($padre_explode) == 2){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","children":[';
        }elseif(count($padre_explode) == 3){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","comment":"'.$padre_explode[2].'","children":[';
        }
        //$string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        $string_f_pathway = (string) NULL;
        $string_f_protocol = (string) NULL;
        //PATHWAY
        $id_pathway = 1000;
        $json_sender_pathway = $this->crear_array_hijos_jsmind_ACTION($array_pathway,$id_pathway+1);
        //PROTOCOL
        $id_protocol = 5000;
        $json_sender_protocol = $this->crear_array_hijos_jsmind_ACTION($array_protocol,$id_protocol+1);
        //DESCRIPTION
        $id_description = 9000;
        $json_sender_description = $this->crear_array_hijos_jsmind_ACTION($array_description,$id_description+1);
        
        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final

        foreach ($padres_split as $keya => $valuea) {
            $elemento = '"'.$valuea[0].'"'; //puede tomar pathway,description, protocol
            if($valuea[0] == 'description'){
                $string_elem_padre .= ',{"id":"'.$id_description.'","topic":'.$elemento.',"direction":"'.$dir.'"';
                for ($i=0; $i < count($json_sender_description); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender_description[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender_description[$i];
                    }
                }
                if($string_f == (string) NULL){
                    $string_elem_padre .= $string_f."}";
                }else{
                    $string_elem_padre .= ",".$string_f."]}"; 
                }
                
            }
            if($valuea[0] == 'ism_transition'){
                //aqui se hace cambio porque en XML dice ism_transition pero en openehr se usa pathway
                $string_elem_padre .= '{"id":"'.$id_pathway.'","topic":"pathway","direction":"'.$dir_2.'"';
                for ($y=0; $y < count($json_sender_pathway); $y++) { 
                    if($y != 0){
                        $string_f_pathway .= ",".$json_sender_pathway[$y];
                    }else{
                        $string_f_pathway = '"children"'.":"."[".$json_sender_pathway[$y];
                    }
                }
                if($string_f_pathway == (string) NULL){
                    $string_elem_padre .= $string_f_pathway."}"; 
                }else{
                    $string_elem_padre .= ",".$string_f_pathway."]}"; 
                }
                
            }
            if($valuea[0] == 'protocol'){
                $string_elem_padre .= ',{"id":"'.$id_protocol.'","topic":'.$elemento.',"direction":"'.$dir_2.'"';
                for ($t=0; $t < count($json_sender_protocol); $t++) { 
                    if($t != 0){
                        $string_f_protocol .= ",".$json_sender_protocol[$t];
                    }else{
                        $string_f_protocol = '"children"'.":"."[".$json_sender_protocol[$t];
                    }
                }
                if($string_f_protocol == (string) NULL){
                    $string_elem_padre .= $string_f_protocol."}";
                }else{
                    $string_elem_padre .= ",".$string_f_protocol."]}";
                }
            }
        }
        if($string_elem_padre == (string) NULL){
            $string_nodo_root .= $string_elem_padre;
            $string_nodo_root .= $attribution_arquetipo;
            $string_nodo_root .= ",".$description_arquetipo."]}"; ;
        }else{
            $string_nodo_root .= $string_elem_padre;
            $string_nodo_root .= ",".$attribution_arquetipo;
            $string_nodo_root .= ",".$description_arquetipo."]}"; ;
        }
        return $string_nodo_root;
    }
    //recorre todos los hijos de un arquetipo action y retorna un arreglo con los hijos listos para ser pegados a su padre
    //especifico
    function crear_array_hijos_jsmind_ACTION($hijos,$id){
        $json_sender_data = array();
        $ind_padre = $id;
        if(is_array($hijos) == TRUE and count($hijos)>1){
            $json_sender_data = array();
            $ind_padre = $id;
            for ($w=0; $w < count($hijos); $w++) {
                if($w != 0){
                    $ind_padre = $ind_padre+50;
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        $padre_array_con_hijos = explode("&&&",$array_con_hijos[0]);
                        for ($q=0; $q < count($array_con_hijos); $q++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $q;
                            if ($q != 0) {
                                $array_con_hijos_explode = explode("&&&",$array_con_hijos[$q]); //funcion explode para separar un string
                                $topic = explode("####",$array_con_hijos_explode[0]);//explode para el tipo de dato
                                if(count($array_con_hijos_explode) == 2 ){ //"topic"=>$array_con_hijos_explode[0]
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$topic[0],"topic"=>$topic[1],"description"=>$array_con_hijos_explode[1]));
                                }elseif(count($array_con_hijos_explode)==3){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$topic[0],"topic"=>$topic[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2]));
                                }elseif(count($array_con_hijos_explode) == 4){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$topic[0],"topic"=>$topic[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2],"source"=>$array_con_hijos_explode[3]));
                                }
                            }
                        }
                        $aTopic = explode("####",$padre_array_con_hijos[0]); // hacemos explode para obtener el tipo de dato
                        if(count($padre_array_con_hijos) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic[0],"topic"=>$aTopic[1],"description"=>$padre_array_con_hijos[1],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic[0],"topic"=>$aTopic[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic[0],"topic"=>$aTopic[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"source"=>$padre_array_con_hijos[3],"children"=>$tmp_array_con_hijos)));
                        }
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        $aTopic2 = explode("####",$explode_string[0]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }elseif(count($explode_string) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1],"comment"=>$explode_string[2],"source"=>$explode_string[3])));
                        }
                    }
                }else{
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        $padre_array_con_hijos = explode("&&&",$array_con_hijos[0]);
                        for ($u=0; $u < count($array_con_hijos); $u++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $u;
                            if ($u != 0) {
                                $array_con_hijos_explode = explode("&&&",$array_con_hijos[$u]); //funcion explode para separar un string
                                $aTopic3 = explode("####",$array_con_hijos_explode[0]);
                                if(count($array_con_hijos_explode) == 2 ){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1]));
                                }elseif(count($array_con_hijos_explode)==3){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2]));
                                }elseif(count($array_con_hijos_explode) == 4){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2],"source"=>$array_con_hijos_explode[3]));
                                }
                            }
                        }
                        //ESTOS IF SON PARA EL TITULO DEL PADRE DE LOS HIJOS DE ARRIBA
                        $aTopic4 = explode("####",$padre_array_con_hijos[0]);
                        if(count($padre_array_con_hijos) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"source"=>$padre_array_con_hijos[3],"children"=>$tmp_array_con_hijos)));
                        }

                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        $aTopic5 = explode("####",$explode_string[0]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }elseif(count($explode_string) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1],"comment"=>$explode_string[2],"source"=>$explode_string[3])));
                        }
                    }
                } 
    
            }
            return $json_sender_data; 
        }else{
            return $json_sender_data; 
        }
    }
    //recorre los hijos de un nodo padre (data, protocol,etc)
    function recorrer_xml_ACTION($xml,$number){
        try {
            $array_state= array();
            for ($h=0; $h < $number; $h++) { 
                $nombre_p = $xml->children[$h]->rm_type_name;
                $id_p = $xml->children[$h]->node_id;
                if ((string)$nombre_p == "CLUSTER") {
                    $cluster_description = $xml->children[$h];
                    $hijos_cluster_description = $this->recorre_hijos($cluster_description);
                    if($hijos_cluster_description == NULL){ //SI DEVUELVE NULL ES QUE NO TIENE MAS HIJOS
                        $array_state[(string)$id_p] = (string)$nombre_p;
                    }else{
                        $array_state[(string)$id_p] = $hijos_cluster_description;
                    }
                    
                }else{
                    $array_state[(string)$id_p] = (string)$nombre_p;
                }
                         
            }
            return $array_state;
        } catch (\Exception $e) {
            return NULL;
        }
    }

//
//
//
// FUNCIONES PARA ARQUETIPO DE TIPO EVALUATION
//
// 
    function crear_mind_jsmind_EVALUATION($aData,$aData1,$aData2,$dir,$description_arquetipo,$attribution_arquetipo,$nombre_a){
        $meta = $this->crear_meta_jsmind($nombre_a,"importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind_EVALUATION($aData,$aData1,$aData2,$dir,$description_arquetipo,$attribution_arquetipo);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}'; //ESTE SE RETORNABA ANTES
        $string_replace = preg_replace("/[\r\n|\n|\r]+/", " ", $string_mind);
        return $string_replace;
    }

    function crear_array_hijos_jsmind_EVALUATION($hijos,$id){
        $json_sender_data = array();
        $ind_padre = $id;
        if(is_array($hijos) == TRUE and count($hijos)>=1){
            for ($w=0; $w < count($hijos); $w++) {
                if($w != 0){
                    $ind_padre = $ind_padre+50;
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        $padre_array_con_hijos = explode("&&&",$array_con_hijos[0]);
                        for ($q=0; $q < count($array_con_hijos); $q++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $q;
                            if ($q != 0) {
                                $array_con_hijos_explode = explode("&&&",$array_con_hijos[$q]); //funcion explode para separar un string
                                $aTopic3 = explode("####",$array_con_hijos_explode[0]);
                                if(count($array_con_hijos_explode) == 2 ){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1]));
                                }elseif(count($array_con_hijos_explode)==3){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2]));
                                }elseif(count($array_con_hijos_explode) == 4){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2],"source"=>$array_con_hijos_explode[3]));
                                }
                            }
                        }
                        $aTopic4 = explode("####",$padre_array_con_hijos[0]);
                        if(count($padre_array_con_hijos) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"source"=>$padre_array_con_hijos[3],"children"=>$tmp_array_con_hijos)));
                        }
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        $aTopic5 = explode("####",$explode_string[0]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }elseif(count($explode_string) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1],"comment"=>$explode_string[2],"source"=>$explode_string[3])));
                        }
                    }
                }else{
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        $padre_array_con_hijos = explode("&&&",$array_con_hijos[0]);
                        for ($u=0; $u < count($array_con_hijos); $u++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $u;
                            if ($u != 0) {
                                $array_con_hijos_explode = explode("&&&",$array_con_hijos[$u]); //funcion explode para separar un string
                                $aTopic0 = explode("####",$array_con_hijos_explode[0]);
                                if(count($array_con_hijos_explode) == 2 ){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic0[0],"topic"=>$aTopic0[1],"description"=>$array_con_hijos_explode[1]));
                                }elseif(count($array_con_hijos_explode)==3){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic0[0],"topic"=>$aTopic0[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2]));
                                }elseif(count($array_con_hijos_explode) == 4){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic0[0],"topic"=>$aTopic0[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2],"source"=>$array_con_hijos_explode[3]));
                                }
                            }
                        }
                        $aTopic1 = explode("####",$padre_array_con_hijos[0]);
                        //ESTOS IF SON PARA EL TITULO DEL PADRE DE LOS HIJOS DE ARRIBA
                        if(count($padre_array_con_hijos) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic1[0],"topic"=>$aTopic1[1],"description"=>$padre_array_con_hijos[1],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic1[0],"topic"=>$aTopic1[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic1[0],"topic"=>$aTopic1[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"source"=>$padre_array_con_hijos[3],"children"=>$tmp_array_con_hijos)));
                        }
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        $aTopic2 = explode("####",$explode_string[0]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }elseif(count($explode_string) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1],"comment"=>$explode_string[2],"source"=>$explode_string[3])));
                        }
                    }
                } 
    
            }
            return $json_sender_data; 
        }else{
            return $json_sender_data; 
        }
    }

    function crear_data_hijos_jsmind_EVALUATION($padres,$hijos,$hijos_prot,$dir,$description_arquetipo,$attribution_arquetipo){
        //lo primero es crear el nodo root, padre de todos los demas nodos
        //NODO ROOT
        //$json_sender_data = array();
        $nodo_root = $padres[0];
        $padre_explode = explode("&&&",$nodo_root);
        //$string_nodo_root = (string) NULL;
        if(count($padre_explode) == 2){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","children":[';
        }elseif(count($padre_explode) == 3){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","comment":"'.$padre_explode[2].'","children":[';
        }elseif(count($padre_explode) == 4){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","comment":"'.$padre_explode[2].'","source":"'.$padre_explode[3].'","children":[';   
        }

        //$string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        $string_f_p = (string) NULL;
        //DATA
        $id_data = 1000;
        $json_sender_data = $this->crear_array_hijos_jsmind_EVALUATION($hijos,$id_data+1);
        //PROTOCOL
        $id_protocol = 5000;
        $json_sender_protocol = $this->crear_array_hijos_jsmind_EVALUATION($hijos_prot,$id_protocol+1);

        $padres_split = array_chunk($padres, 1);//spliteamos nodoroot,data,protocol array(1=>array([0]=>data),
                                                                                        //2=>array([0]=>protocol))
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        foreach($padres_split as $keya => $valuea){ //recorrimos data,protocol
            $elemento = '"'.$valuea[0].'"'; //puede tomar data o protocol
            if($valuea[0] == 'data'){
                $string_elem_padre .= '{"id":"'.$id_data.'","topic":'.$elemento.',"direction":"'.$dir.'"';
                for ($i=0; $i < count($json_sender_data); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender_data[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender_data[$i];
                    }
                }
                if($string_f == (string) NULL){
                    $string_elem_padre .= $string_f."}";
                }else{
                    $string_elem_padre .= ",".$string_f."]}"; 
                }
            }
            if($valuea[0] == 'protocol'){
                $string_elem_padre .= ',{"id":"'.$id_protocol.'","topic":'.$elemento.',"direction":"left"';
                for ($x=0; $x < count($json_sender_protocol); $x++) { 
                    if($x != 0){
                        $string_f_p .= ",".$json_sender_protocol[$x];
                    }else{
                        $string_f_p = '"children"'.":"."[".$json_sender_protocol[$x];
                    }
                }
                if($string_f_p == (string) NULL){
                    $string_elem_padre .= $string_f_p."}";
                }else{
                    $string_elem_padre .= ",".$string_f_p."]}";
                }
            }
        }
        if($string_elem_padre == (string) NULL){
            $string_nodo_root .= $attribution_arquetipo;
            $string_nodo_root .= ",".$description_arquetipo."]}";
        }else{
            $string_nodo_root .= $string_elem_padre;
            $string_nodo_root .= ",".$attribution_arquetipo;
            $string_nodo_root .= ",".$description_arquetipo."]}";
        }
        return $string_nodo_root;
    }


//
//
//
// FUNCIONES PARA ARQUETIPO DE TIPO OBSERVATION
//
//
    function recorrer_xml_OBSERVATION($xml,$number){
        try {
            $array_state= array();
            for ($h=0; $h < $number; $h++) { 
                $nombre_p = $xml->children[$h]->rm_type_name;
                $id_p = $xml->children[$h]->node_id;
                if ((string)$nombre_p == "CLUSTER") {
                    $cluster_description = $xml->children[$h];
                    $hijos_cluster_description = $this->recorre_hijos($cluster_description);
                    if($hijos_cluster_description == NULL){ //SI DEVUELVE NULL ES QUE NO TIENE MAS HIJOS
                        $array_state[(string)$id_p] = (string)$nombre_p;
                    }else{
                        $array_state[(string)$id_p] = $hijos_cluster_description;
                    }
                    
                }else{
                    $array_state[(string)$id_p] = (string)$nombre_p;
                }
                        
            }
            return $array_state;
        } catch (\Exception $e) {
            return NULL;
        }
    }

    function crear_mind_jsmind_OBSERVATION($padres,$array_data,$array_protocol,$array_events,$array_state,$descripcion,$attributo,$nombre_a,$dir){
        $meta = $this->crear_meta_jsmind($nombre_a,"importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind_OBSERVATION($padres,$array_data,$array_protocol,$array_events,$array_state,$descripcion,$attributo,$dir);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }

    function crear_data_hijos_jsmind_OBSERVATION($padres,$array_data,$array_protocol,$array_events,$array_state,$descripcion,$attributo,$dir){ //funcion que crea los hijos de root (jsmind)-------------------
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
        $padre_explode = explode("&&&",$nodo_root);
        if(count($padre_explode) == 2){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","children":[';
        }elseif(count($padre_explode) == 3){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","comment":"'.$padre_explode[2].'","children":[';
        }
        //$string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f_data = (string) NULL; 
        $string_f_protocol = (string) NULL;
        $string_f_eventos = (string) NULL;
        $string_f_state = (string) NULL;

        //data a veces toma state depende del arquetipo,eventos,protocol,state
        //DATA
        $id_pathway = 1000;
        $json_sender_data = $this->crear_array_hijos_jsmind_CLUSTER($array_data,$id_pathway+1);
        //PROTOCOL
        $id_protocol = 20000;
        $json_sender_protocol = $this->crear_array_hijos_jsmind_CLUSTER($array_protocol,$id_protocol+1);
        //print_format($array_protocol);
        //print_format($json_sender_protocol);
        //Events 
        $id_eventos = 50000;
        $json_sender_eventos = $this->crear_array_hijos_jsmind_CLUSTER($array_events,$id_eventos+1);
        //STATE
        $id_state = 70000;
        $json_sender_state = $this->crear_array_hijos_jsmind_CLUSTER($array_state,$id_state+1);

        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        $yjhi = array();
        $bandera_tipos = FALSE;
        foreach ($padres_split as $keya => $valuea) {
            array_push($yjhi,$keya);
            $elemento = '"'.$valuea[0].'"';
            if($valuea[0] == 'data' and $keya ==4){
                $string_elem_padre .= ',{"id":"'.$id_state.'","topic":'.$elemento.',"direction":"'.$dir_2.'"';
                    for ($j=0; $j < count($json_sender_state); $j++) { 
                        if($j != 0){
                            $string_f_data .= ",".$json_sender_state[$j];
                        }else{
                            $string_f_data = '"children"'.":"."[".$json_sender_state[$j];
                        }
                    }
                    if($string_f_data == (string) NULL){
                        $string_elem_padre .= $string_f_data."}";
                    }else{
                        $string_elem_padre .= ",".$string_f_data."]}";
                    }
                    $string_f_data = (string) NULL;
            } 
            if($valuea[0] == 'data' and $keya ==1){
                $string_elem_padre .= '{"id":"'.$id_pathway.'","topic":'.$elemento.',"direction":"'.$dir_2.'"';
                for ($y=0; $y < count($json_sender_data); $y++) { 
                    if($y != 0){
                        $string_f_data .= ",".$json_sender_data[$y];
                    }else{
                        $string_f_data = '"children"'.":"."[".$json_sender_data[$y];
                    }
                }
                if($string_f_data == (string) NULL){
                    $string_elem_padre .= $string_f_data."}";
                }else{
                    $string_elem_padre .= ",".$string_f_data."]}";
                }
            }
            if($valuea[0] == "offset" and $keya ==1){
                $string_elem_padre .= '{"id":"'.$id_eventos.'","topic":"Events","direction":"'.$dir_2.'"';
                for ($u=0; $u < count($json_sender_eventos); $u++) { 
                    if($u != 0){
                        $string_f_eventos.=",".$json_sender_eventos[$u];
                    }else{
                        $string_f_eventos = '"children"'.":"."[".$json_sender_eventos[$u];
                    }
                }
                if($string_f_eventos == (string) NULL){
                    $string_elem_padre .= $string_f_eventos."}";
                }else{
                    $string_elem_padre .= ",".$string_f_eventos."]}";
                }
                $bandera_tipos = TRUE;
            }

            if($valuea[0] == 'Events'  and $keya ==2 and $bandera_tipos == FALSE){
                $string_elem_padre .= ',{"id":"'.$id_eventos.'","topic":'.$elemento.',"direction":"'.$dir_2.'"';
                for ($u=0; $u < count($json_sender_eventos); $u++) { 
                    if($u != 0){
                        $string_f_eventos.=",".$json_sender_eventos[$u];
                    }else{
                        $string_f_eventos = '"children"'.":"."[".$json_sender_eventos[$u];
                    }
                }
                if($string_f_eventos == (string) NULL){
                    $string_elem_padre .= $string_f_eventos."}";
                }else{
                    $string_elem_padre .= ",".$string_f_eventos."]}";
                }
            }

            if($valuea[0] == 'protocol'  and $keya ==3){
                $string_elem_padre .= ',{"id":"'.$id_protocol.'","topic":'.$elemento.',"direction":"'.$dir.'"';
                for ($i=0; $i < count($json_sender_protocol); $i++) { 
                    if($i != 0){
                        $string_f_protocol.=",".$json_sender_protocol[$i];
                    }else{
                        $string_f_protocol = '"children"'.":"."[".$json_sender_protocol[$i];
                    }
                }
                if($string_f_protocol == (string) NULL){
                    $string_elem_padre .= $string_f_protocol."}";
                }else{
                    $string_elem_padre .= ",".$string_f_protocol."]}";
                }
            }
            if($valuea[0] == 'state' and $keya ==4){
                $string_elem_padre .= ',{"id":"'.$id_state.'","topic":'.$elemento.',"direction":"'.$dir_2.'"';
                for ($t=0; $t < count($json_sender_state); $t++) { 
                    if($t != 0){
                        $string_f_state .= ",".$json_sender_state[$t];
                    }else{
                        $string_f_state = '"children"'.":"."[".$json_sender_state[$t];
                    }
                }
                if($string_f_state == (string) NULL){
                    $string_elem_padre .= $string_f_state."}";
                }else{
                    $string_elem_padre .= ",".$string_f_state."]}";
                }
            }

        }

        if($string_elem_padre == (string) NULL){
            $string_nodo_root .= $attributo;
            $string_nodo_root .= ",".$descripcion."]}";
        }else{
            $string_nodo_root .= $string_elem_padre;
            if($attributo != (string) NULL and $descripcion == (string) NULL){
                $string_nodo_root .= ",".$attributo."]}";
            }elseif($descripcion != (string) NULL and $attributo == (string)NULL){
                $string_nodo_root .= ",".$descripcion."]}"; ;
            }elseif($attributo == (string) NULL and $descripcion == (string) NULL){
                $string_nodo_root .= "]}";
            }else{
                $string_nodo_root .= ",".$attributo;
                $string_nodo_root .= ",".$descripcion."]}";
            }
        }
        return $string_nodo_root;
        //$string_nodo_root .= $string_elem_padre."]}";
    }

//
//
//
// FUNCIONES PARA ARQUETIPO DE TIPO CLUSTER
//
//
    function crear_mind_jsmind_CLUSTER($aData,$aData1,$dir,$nombre_a,$descipcion,$attributo){ //AGREGAR----------------------------------------------------
        $meta = $this->crear_meta_jsmind($nombre_a,"importe_editor","1.0");
        $format = $this->crear_format_jsmind("node_tree");
        $hijos = $this->crear_data_hijos_jsmind_CLUSTER($aData,$aData1,$dir,$descipcion,$attributo);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }
    function crear_array_hijos_jsmind_CLUSTER($hijos,$id){
        //print_format($hijos);
        $json_sender_data = array();
        $ind_padre = $id;
        if(is_array($hijos) == TRUE and count($hijos)>=1){
            for ($w=0; $w < count($hijos); $w++) {
                if($w != 0){
                    $ind_padre = $ind_padre+50;
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        //$padre_array_con_hijos = $array_con_hijos[0];
                        $padre_array_con_hijos = explode("&&&",$array_con_hijos[0]);
                        for ($q=0; $q < count($array_con_hijos); $q++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $q;
                            if ($q != 0) {
                                $array_con_hijos_explode = explode("&&&",$array_con_hijos[$q]); //funcion explode para separar un string
                                $aTopic3 = explode("####",$array_con_hijos_explode[0]);
                                if(count($array_con_hijos_explode) == 2 ){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1]));
                                }elseif(count($array_con_hijos_explode)==3){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2]));
                                }elseif(count($array_con_hijos_explode) == 4){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic3[0],"topic"=>$aTopic3[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2],"source"=>$array_con_hijos_explode[3]));
                                }
                                //array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"topic"=>$array_con_hijos[$q]));
                            }
                        }
                        $aTopic4 = explode("####",$padre_array_con_hijos[0]);
                        if(count($padre_array_con_hijos) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic4[0],"topic"=>$aTopic4[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"source"=>$padre_array_con_hijos[3],"children"=>$tmp_array_con_hijos)));
                        }
                        //array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        $aTopic5 = explode("####",$explode_string[0]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }elseif(count($explode_string) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic5[0],"topic"=>$aTopic5[1],"description"=>$explode_string[1],"comment"=>$explode_string[2],"source"=>$explode_string[3])));
                        }
/*                         $explode_string = explode("&&&",$hijos[$w]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        } */
                    }
                }else{
                    if(is_array($hijos[$w]) == TRUE){
                        $array_con_hijos = $hijos[$w];
                        $tmp_array_con_hijos = array();
                        //$padre_array_con_hijos = $array_con_hijos[0];
                        $padre_array_con_hijos = explode("&&&",$array_con_hijos[0]);
                        for ($u=0; $u < count($array_con_hijos); $u++) { //recorremos el arreglo para guardar sus hijos
                            $ind_hijos = $ind_padre + $u;
                            if ($u != 0) {
/*                                 $valor_d = explode(",",$array_con_hijos[$u]);
                                array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"topic"=>$array_con_hijos[$u])); */
                                $array_con_hijos_explode = explode("&&&",$array_con_hijos[$u]); //funcion explode para separar un string
                                $aTopic0 = explode("####",$array_con_hijos_explode[0]);
                                if(count($array_con_hijos_explode) == 2 ){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic0[0],"topic"=>$aTopic0[1],"description"=>$array_con_hijos_explode[1]));
                                }elseif(count($array_con_hijos_explode)==3){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic0[0],"topic"=>$aTopic0[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2]));
                                }elseif(count($array_con_hijos_explode) == 4){
                                    array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"datatype"=>$aTopic0[0],"topic"=>$aTopic0[1],"description"=>$array_con_hijos_explode[1],"comment"=>$array_con_hijos_explode[2],"source"=>$array_con_hijos_explode[3]));
                                }
                            }
                        }
                        //array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
                        $aTopic1 = explode("####",$padre_array_con_hijos[0]);
                        //ESTOS IF SON PARA EL TITULO DEL PADRE DE LOS HIJOS DE ARRIBA
                        if(count($padre_array_con_hijos) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic1[0],"topic"=>$aTopic1[1],"description"=>$padre_array_con_hijos[1],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic1[0],"topic"=>$aTopic1[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"children"=>$tmp_array_con_hijos)));
                        }elseif(count($padre_array_con_hijos) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic1[0],"topic"=>$aTopic1[1],"description"=>$padre_array_con_hijos[1],"comment"=>$padre_array_con_hijos[2],"source"=>$padre_array_con_hijos[3],"children"=>$tmp_array_con_hijos)));
                        }
                    }else{
                        $explode_string = explode("&&&",$hijos[$w]);
                        $aTopic2 = explode("####",$explode_string[0]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        }elseif(count($explode_string) == 4){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"datatype"=>$aTopic2[0],"topic"=>$aTopic2[1],"description"=>$explode_string[1],"comment"=>$explode_string[2],"source"=>$explode_string[3])));
                        }
/*                         $explode_string = explode("&&&",$hijos[$w]);
                        if(count($explode_string) == 2){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1])));
                        }elseif(count($explode_string) == 3){
                            array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$explode_string[0],"description"=>$explode_string[1],"comment"=>$explode_string[2])));
                        } */
                    }
                } 
            }
            return $json_sender_data;
        }else{
            return $json_sender_data;
        } 
    }
    function crear_data_hijos_jsmind_CLUSTER($padres,$array_items,$dir,$descipcion,$attributo){ //funcion que crea los hijos de root (jsmind)-------------------
        //lo primero es crear el nodo root, padre de todos los demas nodos
        //$padres,$array_items,$dir,$descipcion,$attributo
        //NODO ROOT
        //$json_sender_data = array();
        $dir_2 = NULL;
        if($dir == "right"){
            $dir_2 = "left";
        }elseif($dir == "left"){
            $dir_2 = "right";
        }
        $nodo_root = $padres[0];
        $padre_explode = explode("&&&",$nodo_root);
        if(count($padre_explode) == 2){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","children":[';
        }elseif(count($padre_explode) == 3){
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","comment":'.$padre_explode[2].',"children":[';
        }
        //$string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        //PATHWAY
        $id_pathway = 1000;
        $json_sender_items = $this->crear_array_hijos_jsmind_CLUSTER($array_items,$id_pathway+1);

        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        foreach ($padres_split as $keya => $valuea) {
            $elemento = '"'.$valuea[0].'"'; //puede tomar protocol, data,state
            if($valuea[0] == 'items'){
                $string_elem_padre .= '{"id":"'.$id_pathway.'","topic":'.$elemento.',"direction":"'.$dir.'"';
                for ($i=0; $i < count($json_sender_items); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender_items[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender_items[$i];
                    }
                }
                if($string_f == (string) NULL){
                    $string_elem_padre .= $string_f."}";
                }else{
                    $string_elem_padre .= ",".$string_f."]}";
                }
            }

        }

        if($string_elem_padre == (string) NULL){
            $string_nodo_root .= $attributo;
            $string_nodo_root .= ",".$descipcion."]}";
        }else{
            $string_nodo_root .= $string_elem_padre;
            if($attributo != (string) NULL and $descipcion == (string) NULL){
                $string_nodo_root .= ",".$attributo."]}";
            }elseif($descipcion != (string) NULL and $attributo == (string)NULL){
                $string_nodo_root .= ",".$descipcion."]}"; ;
            }elseif($attributo == (string) NULL and $descipcion == (string) NULL){
                $string_nodo_root .= "]}";
            }else{
                $string_nodo_root .= ",".$attributo;
                $string_nodo_root .= ",".$descipcion."]}";
            }
        }
        return $string_nodo_root;
    }
    function recorrer_xml_CLUSTER($xml,$number){
        try {
            $array_state= array();
            for ($h=0; $h < $number; $h++) { 
                $nombre_p = $xml->children[$h]->rm_type_name;
                $id_p = $xml->children[$h]->node_id;
                if ((string)$nombre_p == "CLUSTER") {
                    $cluster_description = $xml->children[$h];
                    $hijos_cluster_description = $this->recorre_hijos($cluster_description);
                    if($hijos_cluster_description == NULL){ //SI DEVUELVE NULL ES QUE NO TIENE MAS HIJOS
                        $array_state[(string)$id_p] = (string)$nombre_p;
                    }else{
                        $array_state[(string)$id_p] = $hijos_cluster_description;
                    }
                    
                }else{
                    $array_state[(string)$id_p] = (string)$nombre_p;
                }
                         
            }
            return $array_state;
        } catch (\Exception $e) {
            return NULL;
        }
    }
    //agregado datatype a evaluation,observation,cluster
}   