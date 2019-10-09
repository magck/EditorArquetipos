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
    
    //aData Arreglo $key -> $value que tiene los codigo -> items del arquetipo
    //aData1 arreglo $key -> $value que tiene los codigo -> item del arquetipo de tipo tree
    list($aData,$aData1) = buscar_item($busca_relacion,'en',$concept); 
    print "<pre>";
    print_r($aData);
    print "</pre>";
    print "\n";
    print "<pre>";
    print_r($aData1);
    print "</pre>";

?>