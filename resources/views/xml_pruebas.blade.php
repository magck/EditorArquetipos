<?php
    $xml = simplexml_load_file("../storage/app/xml_imports/5.xml") or die("Error al cargar el xml");

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
    $concept = (string)parser($xml)->xpath('//a:concept')[0];//Concept es de donde parten (nodo padre)
    $busca = parser($xml)->xpath('//a:node_id'); //Retorna un array de objetos SimpleXMLElement o FALSE en caso de error. 
    $busca_relacion = parser($xml)->xpath('//a:term_definitions');
    $buscar_type_name = parser($xml)->xpath('//a:rm_type_name');
    $busca_attribute_name = parser($xml)->xpath('//a:rm_attribute_name');
    //aData Arreglo $key -> $value que tiene los codigo -> items del arquetipo
    //aData1 arreglo $key -> $value que tiene los codigo -> item del arquetipo de tipo tree
    list($aData,$aData1) = buscar_item($busca_relacion,'en',$concept); 
    $arreglo_elemento = array();
    $arreglo_id = array();
    $arreglo_id_elemento = array();
    for ($i=0; $i < count($buscar_type_name); $i++) { 
        $element = (string) $buscar_type_name[$i];
        $unique_id = (string) $busca[$i];
        if($unique_id == ''){
            $arreglo_id[$i] = 'NO_ID';
        }else{
            $arreglo_id[$i] = $unique_id;
        }
        $arreglo_elemento[$i] = $element;
    }
    for ($i=0; $i < count($arreglo_id); $i++) { 
        if($arreglo_id[$i] == 'NO_ID'){
            $arreglo_id_elemento['NO_ID'.$i]=$arreglo_elemento[$i];
        }else{
            $arreglo_id_elemento[$arreglo_id[$i]]=$arreglo_elemento[$i];
        }
    }
    $intento = $arreglo_id_elemento;
    $llaves = array_keys($intento,'-');
    $separados = implode(",",$intento); //separa por comas el arrego intento y lo hace un string
    $array1 = explode('ITEM_TREE',$separados); //junta por ITEM_TREE
    $array_final_final = array();
    foreach ($array1 as $key => $value) {
        $cadena = (string) $value;
        $arrayf1 = explode(',',$cadena);
        $array_final_final['ITEM_TREE'.$key] = $arrayf1;
    }
    unset($array_final_final['ITEM_TREE0']);
    foreach ($array_final_final as $key => $value) {
        $array_final_final[$key] = array_filter($array_final_final[$key]);
    }
    $types = array('Extension','Last updated');
    $tmp = array();
    foreach ($types as $key => $value) {
        if(in_array($value,$aData)){
            $busqueda = array_search($value,$aData);
            $elemento = $aData[$busqueda];
            $tmp[$busqueda] = $elemento;
            unset($aData[$busqueda]);
        }

    }
    $temporal_3 = $array_final_final;
    foreach ($array_final_final as $key => $value) {
        foreach($value as $llave => $valor){
            if($valor == 'ELEMENT'){
                unset($temporal_3[$key][$llave]);
            }
        }
    }
    $array_final_final = $temporal_3;
    $arreglo_nodos = array();
    foreach ($busca_attribute_name as $key => $value) {
        if($value != 'value' and $value != 'items'){
            $temp = (string) $value;
            array_push($arreglo_nodos,$temp);
        }
    }
    $temporal_4 = $arreglo_nodos;
    foreach ($temporal_4 as $key => $value) {
        if($value == 'defining_code'){
            unset($arreglo_nodos[$key]);
        }
    }
    $indice = 0;
    $arreglo_nodos = array_values($arreglo_nodos);
    foreach ($aData1 as $key => $value) {
        if($value == 'Tree' || $value == 'List' || $value == 'defining_code'){
            $buscar_nodo = array_search($value,$aData1);
            $aData1[$key] = $arreglo_nodos[$indice];
            $indice = $indice +1;
        }
    }

    $aData1 = array_values($aData1);
    $aData = array_values($aData);
    print "<pre>";
    print_r($aData);
    print "</pre>";
    print "\n";
    print "<pre>";
    print_r($aData1);
    print "</pre>";
    print "\n";


    function crear_nodo($id,$parent_id,$isroot,$topic,$background_color,$direction){
        return array('id'=>$id,'parentid'=>$parent_id,'isroot'=>$isroot,'topic'=>$topic,'background-color'=>$background_color,'direction'=>$direction);
    }
    $meta=array('name'=>'archetype','author'=>'editor_import',"version"=>'0.1'); 

    function crear_meta_jsmind($nombre,$autor,$version){
        $string_head = '"meta":{
            "name":"'.$nombre.'",
            "author":"'.$autor.'",
            "version":"'.$version.'"
        },';
        return $string_head;
    }
    function crear_format_jsmind($formato){
        $string_format = '"format":"'.$formato.'",';
        return $string_format;
    }
 
    crear_mind_jsmind($aData,$aData1);

    function crear_mind_jsmind($aData,$aData1){
        $meta = crear_meta_jsmind("archetype","importe_editor","1.0");
        $format = crear_format_jsmind("node_tree");
        $hijos = crear_data_hijos_jsmind($aData,$aData1,"right");
        $string_mind = '{'.$meta.''.$format.'"data":'.$hijos.'}';
        return $string_mind;
    }

    function crear_data_hijos_jsmind($hijos,$padres,$dir){ //funcion que crea los hijos de root (jsmind)
        $json_sender = array();
        $nodo_root = $padres[0];
        $string_nodo_root = '{"id":"root","topic":"'.$nodo_root.'","children":[';
        $string_f = (string) NULL; 
        foreach ($hijos as $keyq => $valueq) {
            $llave = (string) $keyq;
            $valor = (string) $valueq;
            array_push($json_sender,json_encode(array('id'=>'"'.$keyq.'"',"topic"=>$valueq)));
        }
        $padres_split = array_chunk($padres, 1);
        unset($padres_split[0]);
        $string_elem_padre = (string) NULL;
        foreach($padres_split as $keya => $valuea){
            $elemento = '"'.$valuea[0].'"';
            $id_padre = '"'.$keya.'"';
            if($valuea[0] == 'data'){
                $string_elem_padre .= '{"id":'.$id_padre.',"topic":'.$elemento.',"direction":"'.$dir.'",';
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
                $string_elem_padre .= '{"id":'.$id_padre.',"topic":'.$elemento.',"direction":"'.$dir.'","children":""}]}';
            }
        }
        $string_nodo_root .= $string_elem_padre;
        return $string_nodo_root;
    }


