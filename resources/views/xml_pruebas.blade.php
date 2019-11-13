<?php
    $xml = simplexml_load_file("../storage/app/xml_imports/blood_observation.xml") or die("Error al cargar el xml");
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
    function crear_mind_jsmind_CLUSTER($aData,$aData1,$descripcion_de_xml,$dir,$attribution){ //AGREGAR----------------------------------------------------
        $meta = crear_meta_jsmind("archetype","importe_editor","1.0");
        $format = crear_format_jsmind("node_tree");
        $hijos = crear_data_hijos_jsmind_CLUSTER($aData,$aData1,$descripcion_de_xml,$dir,$attribution);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }
    function obtener_hijos_cluster($aT,$padre){ //RECIBE UN PADRE Y RETORNA TODOS SUS HIJOS
        try { //Algunos cluster no tienen hijos, por eso intento contar los hijos
            //$hijos_cluster = $aT->attributes->children;//hijos de cluster--recibe items
            $hijos_cluster = $aT->children;
            $nro_hijos = count($hijos_cluster); //si los cuenta bien
            $nombre_hijos = (string)$aT->children->rm_attribute_name;
        } catch (Exception $e) { //sino los puede contar
            $nro_hijos = FALSE; //no tiene hijos
            //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        if($nro_hijos != FALSE){
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
                    $hijos = obtener_hijos_cluster($new_arg[$n],$arg);
                }
            }
            
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
    function match($tmp_id,$aTrama){ //AGREGUE ESTA FUNCION $array_items,$aTrama
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
                            //$tmp_id[$key][$llave] = $valor_actual_data_2.",".$tmp_id[$key][$llave];
                        }
                    }
                }else{
                    //$tmp_id[$key] =  $aTrama[$key].",".$tmp_id[$key];
                    $tmp_id[$key] =  $aTrama[$key];
                }

            }
        }
        //print_format($tmp_id);
        return $tmp_id;
    }

    function print_format($arg){
        print "<pre>";
        print_r($arg);
        print "</pre>";
        print "\n";
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
        $descripcion_de_xml = crear_description_jsmind_DESCRIPTION($xml,$concept);
        $attribution = crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
        print_format($descripcion_de_xml);
        print_format($attribution);
    }
    if ($tmp_tipo_arquetipo == "OBSERVATION") {
        $concept = (string)parser($xml)->xpath('//a:concept')[0];
        $busca_it = parser($xml)->xpath('//a:definition');
        $busca_term_definition = parser($xml)->xpath('//a:term_definitions');

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
        print_format($def_1_name);

        //STATE
        try {
            $def_2 = $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1];
            $short_def_2 = $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1]->children->attributes->children;
            $def_2_name =(string) $busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1]->rm_attribute_name;
        } catch (\Exception $e) {
            $def_2 = NULL;
            $short_def_2 = NULL;
            $def_2_name = NULL;
        }
        print_format($def_2_name);
        //PROTOCOL
        try {
            $def_3 = $busca_it[0]->attributes[1]->children->attributes->children;
            $def_3_name =(string) $busca_it[0]->attributes[1]->rm_attribute_name;
        } catch (\Exception $q) {
            $def_3 = NULL;
            $def_3_name = NULL;
        }
        print_format($def_3_name);

        //Si tengo Data
        if($def_1 != NULL and $short_def_1 != NULL and $def_1_name != NULL){
            $array_data = recorrer_xml_OBSERVATION($busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[0]->children->attributes,count($short_def_1));
        }else{
            $array_data = array();
        }

        //Si tengo State
        if($def_2 != NULL and $short_def_2 != NULL and $def_2_name != NULL){
            $array_state = recorrer_xml_OBSERVATION($busca_it[0]->attributes[0]->children->attributes->children[0]->attributes[1]->children->attributes,count($short_def_2));
        }else{
            $array_state = array();
        }

        //Si tengo protocol
        if($def_3 != NULL and $def_3_name != NULL){
            $array_protocol = recorrer_xml_OBSERVATION($busca_it[0]->attributes[1]->children->attributes,count($def_3));
        }else{
            $array_protocol = array();
        }

        list($aTrama,$aTrama1) = buscar_item_2($xml,'en',$concept); //obtengo los valores de id->elemento
        //arreglo aTrama que tiene los hijos del nodo root
        $primer_elemento_aTrama1 = reset($aTrama1);
        $aTrama1 = array();
        array_push($aTrama1,$primer_elemento_aTrama1);
        array_push($aTrama1,$def_1_name);
        array_push($aTrama1,$def_2_name);
        array_push($aTrama1,$def_3_name);
        //funcion match para renombrar los arreglos anteriores
        $array_state = array_values_recursive(match($array_state,$aTrama));
        $descripcion = (string) NULL;//crear_description_jsmind_DESCRIPTION($xml,$concept);
        $attributo = (string) NULL;//crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
        $array_data = array_values_recursive(match($array_data,$aTrama));
        $array_protocol = array_values_recursive(match($array_protocol,$aTrama));
        $json_final = crear_mind_jsmind_OBSERVATION($aTrama1,$array_data,$array_protocol,$array_state,"right",$aTrama1[0],$descripcion,$attributo);
        
        //echo $json_final;


   }
    if ($tmp_tipo_arquetipo == "INSTRUCTION") {

    }if ($tmp_tipo_arquetipo == "ADMIN_ENTRY") {

    }if ($tmp_tipo_arquetipo == "COMPOSITION") {

    }if ($tmp_tipo_arquetipo == "clster") {

        $concept = (string)parser($xml)->xpath('//a:concept')[0];
        $busca_it = parser($xml)->xpath('//a:definition');
        $busca_term_definition = parser($xml)->xpath('//a:term_definitions');
        $def_1 = NULL;$def_1_name = NULL;
        try {
            $def_attributes = count($busca_it[0]->attributes);
        } catch (\Exception $e) {
            echo "Arquetipo no cuenta con una etiqueta".$e->getMessage();
        }
        
        if($def_attributes>=1){ //si tengo items
            $def_1 = $busca_it[0]->attributes[0]; //items para procesar en xml
            $short_def_1 = $busca_it[0]->attributes[0]->children;
            $def_1_name =(string) $busca_it[0]->attributes[0]->rm_attribute_name; //items
        }
        if($def_1 != NULL and $def_1_name != NULL){
            $array_items = recorrer_xml_OBSERVATION($def_1,count($short_def_1));
        }
        list($aTrama,$aTrama1) = buscar_item_2($xml,'en',$concept); //obtengo los valores de id->elemento
        buscar_item_2($xml,'en',$concept);
        //arreglo aTrama que tiene los hijos del nodo root, los valores que tiene todo el arquetipo
        //print_format($aTrama);
        //print_format($aTrama1);
        //print_format($aTrama1);
        $primer_elemento_aTrama1 = reset($aTrama1);
        $aTrama1 = array();
        array_push($aTrama1,$primer_elemento_aTrama1);
        array_push($aTrama1,$def_1_name);

        //funcion match para renombrar los arreglos anteriores
        $array_items = array_values_recursive(match($array_items,$aTrama));
        //print_format($array_items);

        $descripcion_de_xml = crear_description_jsmind_DESCRIPTION($xml,$concept);
        $attribution = crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
        echo crear_mind_jsmind_CLUSTER($aTrama1,$array_items,$descripcion_de_xml,"left",$attribution);
        //print_format($descripcion_de_xml);

    }if ($tmp_tipo_arquetipo == "SECTION") {

    }
    function buscar_item_2($xml,$lenguage_default,$concept_xml){
        $arreglo_code_items = array();
        $arreglo_nodo_root = array();
        $term_definition = parser($xml)->xpath('//a:term_definitions');
        for ($w=0; $w < count($term_definition); $w++) { 
            if((string)$term_definition[$w]->attributes() == $lenguage_default){
                $items_recorrer = $term_definition[$w]->items;
                for ($q=0; $q < count($items_recorrer); $q++) {
                    //print_format($items_recorrer[$q]);
                    $items_de_id = array_to_string($items_recorrer[$q]);
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

    function recorrer_xml_OBSERVATION($xml,$number){
        try {
            $array_state= array();
            for ($h=0; $h < $number; $h++) { 
                $nombre_p = $xml->children[$h]->rm_type_name;
                $id_p = $xml->children[$h]->node_id;
                if ((string)$nombre_p == "CLUSTER") {
                    $cluster_description = $xml->children[$h];
                    $hijos_cluster_description = recorre_hijos($cluster_description);
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

    function crear_mind_jsmind_OBSERVATION($aData,$aData1,$aData2,$aData3,$dir,$nombre_a,$descripcion,$attributo){
        $meta = crear_meta_jsmind($nombre_a,"importe_editor","1.0");
        $format = crear_format_jsmind("node_tree");
        $hijos = crear_data_hijos_jsmind_OBSERVATION($aData,$aData1,$aData2,$aData3,$dir,$descripcion,$attributo);
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }

    function crear_data_hijos_jsmind_OBSERVATION($padres,$array_data,$array_description,$array_protocol,$dir,$descripcion,$attributo){ //funcion que crea los hijos de root (jsmind)-------------------
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
        $string_f = (string) NULL; 
        $string_f_pathway = (string) NULL;
        $string_f_protocol = (string) NULL;
        //PATHWAY
        $id_pathway = 1000;
        $json_sender_pathway = crear_array_hijos_jsmind_CLUSTER($array_data,$id_pathway+1);
        //PROTOCOL
        $id_protocol = 20000;
        $json_sender_protocol = crear_array_hijos_jsmind_CLUSTER($array_protocol,$id_protocol+1);
        //DESCRIPTION
        $id_description = 50000;
        $json_sender_description = crear_array_hijos_jsmind_CLUSTER($array_description,$id_description+1);
        
        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        foreach ($padres_split as $keya => $valuea) {
            $elemento = '"'.$valuea[0].'"'; //puede tomar protocol, data,state,events
            if($valuea[0] == 'protocol'){
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
            if($valuea[0] == 'data'){
                $string_elem_padre .= '{"id":"'.$id_pathway.'","topic":'.$elemento.',"direction":"'.$dir_2.'"';
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
            if($valuea[0] == 'state'){
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

    function crear_description_jsmind_DESCRIPTION($xml,$concept){
        $details = parser($xml)->xpath('//a:details');
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
                $ref_for = parser($xml)->xpath('//a:other_details');
                $referencia = (string) NULL;
                try {
                    for ($t=0; $t < count($ref_for); $t++) { 
                        if ((string)$ref_for[$t]->attributes() == 'references') {
                            $referencia = array("references",(string)$ref_for[$t][0]);
                        }
                    }
                } catch (\Exception $e) {
                    $referencia = NULL;
                }
                $conc = parser($xml)->xpath('//a:ontology');
                try {
                    foreach ($conc[0]->term_definitions as $ll => $vv) {
                        if((string)$vv->attributes()->language == "en"){
                            if($vv->items['code'] == $concept){
                                try {
                                    $concept_description = array("Concept description",(string)$vv->items->items[1]);
                                } catch (\Exception $e) {
                                    $concept_description = NULL;
                                }
                            }
                            
                        }
                        
                    }
                } catch (\Exception $e) {
                    $concept_description = NULL;
                }
                $array_hijos_description = array();
                array_push($array_hijos_description,$concept_description);
                array_push($array_hijos_description,$proposito);
                array_push($array_hijos_description,$use);
                //array_push($array_hijos_description,$misuse);
                array_push($array_hijos_description,$keywords);
                array_push($array_hijos_description,$referencia);
                //print_format($array_hijos_description);
                try {
                    $ide_description = 200;
                    $hijos_description = crear_array_hijos_jsmind_CLUSTER($array_hijos_description,$ide_description+1);
                    
                    $string_description = '{"id":"'.$ide_description.'","topic":"description","direction":"right","children":[';
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
            $ruta_archetype = (string)parser($xml)->xpath('//a:archetype_id')[0]->value;
            $archetype_id = array("Archetype ID:",$ruta_archetype);
        } catch (\Exception $e) {
            $archetype_id = array("Archetype ID:",NULL);
        }
        try {
            $M_V_ID = "Major Version ID:".(string) parser($xml)->xpath('//a:uid')[0]->value;
            $ref_other_d =parser($xml)->xpath('//a:other_details');
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
            $ref_original_autor = parser($xml)->xpath('//a:original_author');
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
            $ref_custodian = parser($xml)->xpath('//a:other_details');
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
            $ref_contri = parser($xml)->xpath('//a:other_contributors');
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
            $translations = parser($xml)->xpath("//a:translations");
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
            $licencia = parser($xml)->xpath("//a:details");
            $lic = parser($xml)->xpath("//a:other_details");
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
            $hijos_attribution = crear_array_hijos_jsmind_CLUSTER($array_hijos_attibution,$id_attribution+1);
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
    function crear_array_hijos_jsmind_CLUSTER($hijos,$id){
        $json_sender_data = array();
        $ind_padre = $id;
        if(is_array($hijos) == TRUE and count($hijos)>1){
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
            //print_format($json_sender_data);
            return $json_sender_data; 
        }else{
            return $json_sender_data; 
        }
    }

    function crear_data_hijos_jsmind_CLUSTER($padres,$array_items,$descripcion_de_xml,$dir,$attribution){ //funcion que crea los hijos de root (jsmind)-------------------
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
            $string_nodo_root = '{"id":"root","topic":"'.$padre_explode[0].'","description":"'.$padre_explode[1].'","comment":'.$padre_explode[2].',"children":[';
        }
        //$string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        //PATHWAY
        $id_pathway = 1000;
        $json_sender_items = crear_array_hijos_jsmind_CLUSTER($array_items,$id_pathway+1);

        
        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]); //sacamos el nodo root 
        $string_elem_padre = (string) NULL; //string final
        foreach ($padres_split as $keya => $valuea) {
            $elemento = '"'.$valuea[0].'"'; //puede tomar protocol, data,state
            if($valuea[0] == 'items'){
                $string_elem_padre .= '{"id":"'.$id_pathway.'","topic":'.$elemento.',"direction":"'.$dir.'",';
                for ($i=0; $i < count($json_sender_items); $i++) { 
                    if($i != 0){
                        $string_f.=",".$json_sender_items[$i];
                    }else{
                        $string_f = '"children"'.":"."[".$json_sender_items[$i];
                    }
                }
                $string_elem_padre .= $string_f."]}"; 
            }

        }
        
        //$string_nodo_root .= $string_elem_padre."]}";
        $string_nodo_root .= $string_elem_padre;
        $string_nodo_root .= ",".$attribution;
        $string_nodo_root .= ",".$descripcion_de_xml."]}"; ;
        return $string_nodo_root;
    }
 
    
    //definicion -> atributos(data, protocol)->hijos de (data,protocol)->atributos de data protocol

