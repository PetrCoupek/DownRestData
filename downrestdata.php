<?php

/** Command line PHP tool script for downloading spatial data from the ArcGIS REST/API server
 *  final GeoJSON is to be imported into QGIS using the following approach:
 *  menu Layer -> add vector layer -> JSON file -> button Add  .
 *  the class construct parameters
 *  @param $datasource  - URL path to the ArcGIS mapservice ended wuth /n/ , where n is the layer number
 *  @param $filename - jmeno vystupniho souboru JSON bez koncovky
 *  @param $limit - maximalni pocat najednou stazitelnych zaznamu (je omezovano )  
 *  18.11.2021, 27.01.2022
 */ 

class DownRestData {

  function __construct(){
     $this->version=1.0;
  }
  
  /**
   * @param string $datasource - the ArcGIS mapservice URL
   * @param string $filename - the file name for the result json file
   * @param int $limit - the amount of features per one GET request
   * result is stored as a file on the drive
   */

  function get($datasource,$filename,$limit=990){  
    
    $limit_id=1e10; /* max ID limitation, see below */

    /* let's obtain all the unique identifiers - typically  objectid's */

    $req=$datasource.'query?where=1%3D1&geometryType=esriGeometryEnvelope&spatialRel=esriSpatialRelIntersects&returnGeometry=false&'.
     'returnTrueCurves=false&returnIdsOnly=true&returnCountOnly=false&returnZ=false&returnM=false&returnDistinctValues=false&'.
    '&returnExtentOnly=false&f=pjson';
    $ids=json_decode($this->httpRequest($req),true);
    $id_name=$ids['objectIdFieldName'];
    $ids=$ids['objectIds'];
    echo $id_name,' ',memory_get_usage(true),'/',ini_get('memory_limit'),"\n";

    /* let's obtain attribute names */
    $capabilities=json_decode($this->httpRequest($datasource.'?f=json'),true);
    $fields=$capabilities['fields'];
    $outFields='';
    for ($i=0;$i<count($fields);$i++){
     if ($fields[$i]['name']!='shape' && $fields[$i]['type']!='esriFieldTypeGeometry'){
      $outFields.=($outFields==''?'':'%2C').$fields[$i]['name']; 
     }
    }
  
    /* let's prepare the requests by not exceeding the maximal number of reatures */
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
        /* or, to stop by getting this maximal ID */
        array_push($req,array($id_from,$ids[$i]));
        break;
      }  
    }
  
    echo memory_get_usage(true),'/',ini_get('memory_limit'),"\n";
    unset($ids);
    echo memory_get_usage(true),'/',ini_get('memory_limit'),"\n";
    
    /* in large datasets, this operation could be easily exhaust the operational memory for the PHP script,
     * so the individual responses are stored to the disc drive and processed at the end of download
     */
    
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
       /* save only the array of features */

      unset($a); /* release memory */
      unset($f);
    }

    /* final completation - only as mainpulation with a text files - is less memory 
     * critical than JSON-object processing 
     */
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
     /* the simple situation - the total number of the features/records is under the limit and can be downloaded at once */
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
        echo "Cannot write into file $filename .\n";
      }else{
        echo "Stored in the file $filename .\n";
      }
    }   
  }
}

/** It saves only downloaded part
 * @param string $filename
 * @param string $part - content 
 */
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
      echo "Cannot write into file $filename .\n";
    }else{
      echo "Stored in the file $filename .\n";
    }
  }
  return 0;
}

/** It reads the strored part
 * @param string $filename
 * @param int $index 
 * @param bool $last
 */
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

/**
 * @param array $tree JSON string
 * @return string  
 */
function json_part($tree){
  
  $ret=substr(json_encode($tree,
  JSON_PRETTY_PRINT + 
  JSON_UNESCAPED_SLASHES + 
  JSON_UNESCAPED_UNICODE ),1,-1);
  
  return $ret;
}

function readpart000($filename,$index,$last){
  $filename='tmp/'.$filename;
  $is_data=($index==0); /* if index is 1, read immidiatelly from the begining */
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
  /* last rows are stripped at the end of the Featurecollection */
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
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    if ($user !== null || $pass !== null) {
      curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
    }
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); /* no echo, just return result */
    if (!ini_get('open_basedir')) {
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); /* sometime is useful :) */
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

} /* of class */

?>