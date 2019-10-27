<?php
    $xml = simplexml_load_file("../storage/app/xml_imports/diagnosis.xml") or die("Error al cargar el xml");

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
    function buscar_item2($padre,$idioma,$concepto){
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
    function obtener_hijos_cluster($aT){ //RECIBE UN PADRE Y RETORNA TODOS SUS HIJOS
        try { //Algunos cluster no tienen hijos, por eso intento contar los hijos
            $hijos_cluster = $aT->attributes->children;//hijos de cluster
            $nro_hijos = count($hijos_cluster); //si los cuenta bien
        } catch (Exception $e) { //sino los puede contar
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
            $hijos = obtener_hijos_cluster($arg);
        } catch (Exception $e) {
            $hijos = NULL;
        }
        if($hijos != NULL){ //Si tiene hijos y ahora debo verificar si dentro de sus hijos hay un cluster
            $padre_siguientes = $arg->attribute->children;
            recorre_hijos($padre_siguientes);
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
        list($aTrama,$aTrama1) =buscar_item($busca_term_definition,'en',$concept);
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
        //tmp_id tiene todos los hijos de data
        //aTrama es el diccionario de todos los id => item_definition
        $nodo_root = $aTrama1[$concept];
        $aTrama1 = array();
        array_push($aTrama1,$nodo_root);
        array_push($aTrama1,$def_1_name);
        array_push($aTrama1,$def_2_name);

        print "<pre>";
        print_r($aTrama);
        print "</pre>";
        print "\n";
        print "<pre>";
        print_r($aTrama1);
        print "</pre>";
        print "\n";
        print "<pre>";
        print_r($tmp_id);
        print "</pre>";
        print "\n";
        
    }
    if ($tmp_tipo_arquetipo == "OBSERVATION") {

    }
    if ($tmp_tipo_arquetipo == "INSTRUCTION") {

    }if ($tmp_tipo_arquetipo == "ADMIN_ENTRY") {

    }if ($tmp_tipo_arquetipo == "COMPOSITION") {

    }if ($tmp_tipo_arquetipo == "CLUSTER") {

    }if ($tmp_tipo_arquetipo == "SECTION") {
    }



    
    //definicion -> atributos(data, protocol)->hijos de (data,protocol)->atributos de data protocol

