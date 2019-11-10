<?php
    $xml = simplexml_load_file("../storage/app/xml_imports/absence_evaluation.xml") or die("Error al cargar el xml");
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
        $descripcion_de_xml = crear_description_jsmind_DESCRIPTION($xml,$concept);
        $attribution = crear_attribution_jsmind_ATTRIBUTION($xml,$concept);
        print_format($descripcion_de_xml);
        print_format($attribution);
    }
    if ($tmp_tipo_arquetipo == "OBSERVATION") {

    }
    if ($tmp_tipo_arquetipo == "INSTRUCTION") {

    }if ($tmp_tipo_arquetipo == "ADMIN_ENTRY") {

    }if ($tmp_tipo_arquetipo == "COMPOSITION") {

    }if ($tmp_tipo_arquetipo == "CLUSTER") {

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
        list($aTrama,$aTrama1) = buscar_item($busca_term_definition,'en',$concept); //obtengo los valores de id->elemento
        //arreglo aTrama que tiene los hijos del nodo root
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

    function crear_array_hijos_jsmind_CLUSTER($hijos,$id){
        $json_sender_data = array();
        $ind_padre = $id;
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
                    array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$hijos[$w])));
                }
            }else{
                if(is_array($hijos[$w]) == TRUE){
                    $array_con_hijos = $hijos[$w];
                    $tmp_array_con_hijos = array();
                    $padre_array_con_hijos = $array_con_hijos[0];
                    for ($u=0; $u < count($array_con_hijos); $u++) { //recorremos el arreglo para guardar sus hijos
                        $ind_hijos = $ind_padre + $u;
                        if ($u != 0) {
                            array_push($tmp_array_con_hijos,array('id'=>(string)$ind_hijos,"topic"=>$array_con_hijos[$u]));
                        }
                    }
                    array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$padre_array_con_hijos,"children"=>$tmp_array_con_hijos)));
                }else{
                    array_push($json_sender_data,json_encode(array('id'=>(string)$ind_padre,"topic"=>$hijos[$w])));
                }
            } 

        }
        return $json_sender_data;
        /*foreach ($hijos as $keyq => $valueq) {
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

        }*/
        
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
        $string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
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

