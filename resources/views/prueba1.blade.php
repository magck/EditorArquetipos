<?php
 //CODIGO HASTA EVALIATION FUNCIONANDO
    $xml = simplexml_load_file("../storage/app/xml_imports/gender.xml") or die("Error al cargar el xml");
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
                $arr2[] = array_values_recursive($value);
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
    function crear_mind_jsmind($aData,$aData1,$aData2){ //AGREGAR----------------------------------------------------
        $meta = crear_meta_jsmind("archetype","importe_editor","1.0");
        $format = crear_format_jsmind("node_tree");
        $hijos = crear_data_hijos_jsmind($aData,$aData1,$aData2,"right");
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }
    function obtener_hijos_cluster($aT,$padre){ //RECIBE UN PADRE Y RETORNA TODOS SUS HIJOS
        try { //Algunos cluster no tienen hijos, por eso intento contar los hijos
            //$hijos_cluster = $aT->attributes->children;//hijos de cluster--recibe items
            $hijos_cluster = $aT->children;
            $nro_hijos = count($hijos_cluster); //si los cuenta bien
            $nombre_hijos = (string)$aT->children->rm_attribute_name;
            print_format($nombre_hijos);
        } catch (Exception $e) { //sino los puede contar
            $nro_hijos = FALSE; //no tiene hijos
            //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        if($nro_hijos != FALSE){
            echo "paso arriba";
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
                    //print "paso items".$n;
                    print_format($new_arg[$n]->rm_attribute_name);
                    $hijos = obtener_hijos_cluster($new_arg[$n],$arg);
                    print_format($hijos);
                }
            }
            
            //print_format(count($hijos));
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

    $tipos_conocidos = array('openEHR-EHR-ACTION','openEHR-EHR-EVALUATION','openEHR-EHR-OBSERVATION',
                                "openEHR-EHR-INSTRUCTION","openEHR-EHR-ADMIN_ENTRY","openEHR-EHR-COMPOSITION"
                            ,"openEHR-EHR-CLUSTER","openEHR-EHR-SECTION");
    $tipo_arquetipo_xml = (string)parser($xml)->xpath('//a:archetype_id')[0]->value;
    $tipo_actual_arquetipo = explode(".",$tipo_arquetipo_xml)[0];

    foreach ($tipos_conocidos as $value) {
        if($value === $tipo_actual_arquetipo){
           $tmp_tipo_arquetipo = explode("-",$value)[2];
        }
    }
    if($tmp_tipo_arquetipo == "ACTION"){
    

    }
    if($tmp_tipo_arquetipo == "EVALUATION") {
        $concept = (string)parser($xml)->xpath('//a:concept')[0];
        $busca_it = parser($xml)->xpath('//a:definition');
        $busca_term_definition = parser($xml)->xpath('//a:term_definitions');
        $def_attributes = count($busca_it[0]->attributes);

        $def_data = NULL;
        if($def_attributes>=2){ //si tengo data y protocol
            $def_1 = $busca_it[0]->attributes[0]->children->attributes->children;
            $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name;
            $def_2 = $busca_it[0]->attributes[1]->children->attributes->children;
            $def_2_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
        }
        //FOR PARA DATA
        $tmp_id = [];
        
        for ($i=0; $i < count($def_1); $i++) { 
            $nombre = (string)$busca_it[0]->attributes[0]->children->attributes->children[$i]->rm_type_name; //todos los hijos de data,hasta children[$i] es cluster
            $id = $busca_it[0]->attributes[0]->children->attributes->children[$i]->node_id ; //id de hijos de data
            if($nombre == "CLUSTER"){//si dentro de data encuentro un nodo llamado cluster
                $cluster = $busca_it[0]->attributes[0]->children->attributes->children[$i];
                $hijos_c = recorre_hijos($cluster);
                $tmp_id[(string)$id] = $hijos_c;
            }else{
                $tmp_id[(string)$id] = $nombre;
            }


        }    

        //FOR PARA PROTOCOL
        $tmp_pro= array();//AGREGUE ESTE FOR
        for ($h=0; $h < count($def_2); $h++) { 
            $nombre_p = (string)$busca_it[0]->attributes[1]->children->attributes->children[$h]->rm_type_name;
            $id_p =(string) $busca_it[0]->attributes[1]->children->attributes->children[$h]->node_id;
            if($nombre_p == "CLUSTER"){
                $cluster_p = $busca_it[0]->attributes[1]->children->attributes->children[$h];
                $hijos_c_p = recorre_hijos($cluster_p);
                $tmp_pro[(string)$id_p] = $hijos_c_p;
            }else{
                $tmp_pro[(string)$id_p] = $nombre_p;
            }
            
        }
        print_format($tmp_pro);
        list($aTrama,$aTrama1) =buscar_item($busca_term_definition,'en',$concept);

        //tmp_id tiene todos los hijos de data
        //aTrama es el diccionario de todos los id => item_definition
        $tmp_id = match($tmp_id,$aTrama); //ESTO AGREGADO
        $hijos_protocol = match($tmp_pro,$aTrama);
        $nodo_root = $aTrama1[$concept];
        $aTrama1_padres = array();
        $aTrama2_hijos = $tmp_id;
        array_push($aTrama1_padres,$nodo_root);
        array_push($aTrama1_padres,$def_1_name);
        array_push($aTrama1_padres,$def_2_name);
        
        $aTrama2_hijos = array_values_recursive($aTrama2_hijos);
        $aTrama3_hijos_c = array_values_recursive($hijos_protocol);
        //print_format($aTrama1_padres);
        //print_format($aTrama2_hijos);
        //print_format($opcional);
        print_format($aTrama3_hijos_c);
        print_format($aTrama1_padres);
        echo crear_mind_jsmind($aTrama1_padres,$aTrama2_hijos,$aTrama3_hijos_c,"right");
        
    }
    if ($tmp_tipo_arquetipo == "OBSERVATION") {

    }
    if ($tmp_tipo_arquetipo == "INSTRUCTION") {

    }if ($tmp_tipo_arquetipo == "ADMIN_ENTRY") {

    }if ($tmp_tipo_arquetipo == "COMPOSITION") {

    }if ($tmp_tipo_arquetipo == "CLUSTER") {

    }if ($tmp_tipo_arquetipo == "SECTION") {
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

    function print_format($arg){
        print "<pre>";
        print_r($arg);
        print "</pre>";
        print "\n";
    }
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
        $json_sender_data = crear_array_hijos_jsmind($hijos,100);
        //PROTOCOL
        $json_sender_protocol = crear_array_hijos_jsmind($hijos_prot,600);

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
