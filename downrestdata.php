<?php

/** Command line PHP script for download daptial data from the ArcGIS rest API server
 *  final GeoJSON could be imported into QGIS using the following approach:
 *  Menu Layer -> add vector layer -> JSON  file -> button Add  .
 *  the class construct parameters
 *  @param $datasource  - URL cesta k mapove sluzbe zakoncena /n/ , kde n je cislo vrstvy
 *  @param $filename - jmeno vystupniho souboru JSON bez koncovky
 *  @param $limit - maximalni pocat najednou stazitelnych zaznamu (je omezovano )  
 *  18.11.2021, 27.01.2022
 */ 

class DownRestData {

  /**
   * @param string $datasource - the ArcGIS mapservice URL
   * @param string $filename - the file name for the result json file
   * @param int $limit - the amount of features per one GET request
   */
  function __construct(){
     //$this->datasource=$datasource;
     //$this->filename=$filename;
  }
  
    /* ziskani vsech jedinecnych identifikatoru ve vrstve a jmena identifikatoru - typicky objectid */
  function get($datasource,$filename,$limit=990){  
    $req=$datasource.'query?where=1%3D1&geometryType=esriGeometryEnvelope&spatialRel=esriSpatialRelIntersects&returnGeometry=false&'.
     'returnTrueCurves=false&returnIdsOnly=true&returnCountOnly=false&returnZ=false&returnM=false&returnDistinctValues=false&'.
    '&returnExtentOnly=false&f=pjson';
    $ids=json_decode($this->httpRequest($req),true);
    $id_name=$ids['objectIdFieldName'];
    $ids=$ids['objectIds'];
    echo $id_name,' ',memory_get_usage(true),'/',ini_get('memory_limit'),"\n";

    /* nacti jmena atributu */
    $capabilities=json_decode($this->httpRequest($datasource.'?f=json'),true);
    $fields=$capabilities['fields'];
    $outFields='';
    for ($i=0;$i<count($fields);$i++){
     if ($fields[$i]['name']!='shape' && $fields[$i]['type']!='esriFieldTypeGeometry'){
      $outFields.=($outFields==''?'':'%2C').$fields[$i]['name']; 
     }
    }
  
    /* priprava jednotlivych get requestu tak, aby zadny nepresahl maximalni pocet hodnot */
    $req=array();

    if (count($ids)>$limit){
    for ($i=0, $n=0, $id_from=0; $i<count($ids); $i++,$n++){
      if ($id_from==0){
        $n=0;
        $id_from=$ids[$i];
      }
      if ($n>=$limit){
        array_push($req,array($id_from,$ids[$i]));
        $n=0;
        $id_from=0;
      }
      if ($ids[$i]>$limit_id) {
        /* bez ohledu na celkovy pocet zaznamu lze skoncit prekrocenim tohoto ID */
        array_push($req,array($id_from,$ids[$i]));
        break;
      }  
    }
  
    echo memory_get_usage(true),'/',ini_get('memory_limit'),"\n";
    unset($ids);
    echo memory_get_usage(true),'/',ini_get('memory_limit'),"\n";
    
     /* protoze operace muze byt pametove narocna, jsou jednotlive casti requestu ukladany prubezne na disk ke konecenmu zpacovani */
    for($i=0; $i<count($req); $i++){
      $id_from=$req[$i][0];
      $id_to=$req[$i][1];
      echo "$id_from - $id_to ; memory: ",memory_get_usage(true),'/',ini_get('memory_limit'),"\n";
      $f=$this->httpRequest($datasource.'query?'.
       'where='.$id_name.'%3E'.$id_from.'+and+'.$id_name.'%3C'.$id_to.'&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects'.
       '&relationParam=&outFields='.$outFields.'&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR='.
       '&having=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false'.
       '&returnM=false&gdbVersion=&historicMoment=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance='.
       '&returnExtentOnly=false&datumTransformation=&parameterValues=&rangeValues=&quantizationParameters=&f=geojson');

      $a=json_decode($f,true);
      $this->savepart($filename.'_'.$i.'.json',$a);
       /* zachran jen pole features */

      unset($a); /* uvolneni pameti */
      unset($f);
    }

    /* kompletace vysledneho souboru je jen manipulace s textovymi soubory - bez naroku na pamet */
    $handle=fopen($filename.'.json','w');
    for($i=0;$i<count($req);$i++){
      fwrite($handle,$this->readpart($filename,$i,$i+1==count($req)));
    }  
    /*for($i=0;$i<count($req);$i++){
        fwrite($handle,
            readpart($filename,$i,$i<count($req)-1));
    }*/
    fclose($handle);

    }else{
     /* jednodussi varianta - celkovy pocet zaznamu je pod limitem a je mozno ho nacist najednou */
      $f=$this->httpRequest($datasource.'query?'.
      'where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects'.
      '&relationParam=&outFields='.$outFields.'&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR='.
      '&having=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false'.
      '&returnM=false&gdbVersion=&historicMoment=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance='.
      '&returnExtentOnly=false&datumTransformation=&parameterValues=&rangeValues=&quantizationParameters=&f=geojson');
      $a=json_decode($f,true);
      if ($handle = fopen($filename, 'w')){
      if (fwrite($handle, 
        json_encode($a,JSON_PRETTY_PRINT + 
                       JSON_UNESCAPED_SLASHES + 
                       JSON_UNESCAPED_UNICODE)) === FALSE) {
        echo "Nemohu zapsat do souboru $filename .\n";
      }else{
        echo "Zapsano do souboru $filename .\n";
      }
    }   
  }
}
  
function savepart($filename,$part){
  if (!is_dir('tmp')){
    mkdir('tmp');
  }
  $filename='tmp/'.$filename;
  if ($handle = fopen($filename, 'w')){
    if (fwrite($handle, 
     json_encode($part,JSON_PRETTY_PRINT + 
                      JSON_UNESCAPED_SLASHES + 
                      JSON_UNESCAPED_UNICODE)) === FALSE) {
      echo "Nemohu zapsat do souboru $filename .\n";
    }else{
      echo "Zapsano do souboru $filename .\n";
    }
  }
  return 0;
}

function readpart($filename,$index,$last){
  $filename='tmp/'.$filename.'_'.$index.'.json';
  $tmp=json_decode(file_get_contents($filename),true);
  if ($index==0){
    $ret='{      '."\n".
       '"type": "FeatureCollection",'."\n".
       '"crs":{'."\n".
        $this->json_part($tmp['crs'])."\n".
       '},'."\n". 
       '"features": [';
  }else{
    $ret='';
  }
  if ($index>0) $ret.=',';
  $ret.=$this->json_part($tmp['features']);
  if ($last) {
    $ret.=']'."\n".'}'."\n";
  }
  return $ret;
} 

function json_part($tree){
  $ret=substr(json_encode($tree,
  JSON_PRETTY_PRINT + 
  JSON_UNESCAPED_SLASHES + 
  JSON_UNESCAPED_UNICODE ),1,-1);
  
  return $ret;
}

function readpart000($filename,$index,$last){
  $filename='tmp/'.$filename;
  $is_data=($index==0); /* pokud je index 1, cte se hned od zacatku */
  $data=array();
  if ($handle = fopen($filename.'_'.$index.'.json', 'r')){
    while (($line = fgets($handle)) !== false) {
      if ($is_data) array_push($data,$line);
      if (strpos($line,'"features": [')!==false){
        $is_data=true;
      }
    }
    fclose($handle);
  }
  /* posledni radky s uzavrenim pole Featurecollection se odrezou */
  $n=count($data);
  if (trim($data[$n-1])=='}')  array_pop($data);
  if (trim($data[$n-2]))
  if (trim($data[$n-2])==']' || trim($data[$n-2])=='],') {
    
    echo trim($data[$n-2]),'**';
    array_pop($data);
  } 
  if (trim($data[$n-3])=='}' && !$last )  $data[$n-3].=',';
  return $data;  
}

/**
	 * Process HTTP request.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string|false
	 * @throws FeedException
	 */ 
function httpRequest($url,  $user=null, $pass=null)
{
  if (extension_loaded('curl')) {
    //echo 'je' ;die;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    if ($user !== null || $pass !== null) {
      curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
    }
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // no echo, just return result
    if (!ini_get('open_basedir')) {
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // sometime is useful :)
    }
    $result = curl_exec($curl);
    return curl_errno($curl) === 0 && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200
      ? $result
      : false;

  } elseif ($user === null && $pass === null) {
    return file_get_contents($url);

  } else {
    throw new Exception('PHP extension CURL is not loaded.');
  }
}

} /* od class */

?>