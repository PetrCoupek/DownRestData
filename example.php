<?php
  /** Example can be called from the command line using php engine:
   * php -f example.php 
   */ 

  include_once "downrestdata.php";

  $g=new DownRestData();
  $g->get('https://gis.nature.cz/arcgis/rest/services/UzemniOchrana/ChranUzemi/MapServer/0/',
          'maloplosne');

?>