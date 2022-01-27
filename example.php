<?php
  /** Example of the call - the calling from the command line:
   * php -f example.php */ 

  include_once "downrestdata.php";

  $g=new DownRestData();
  $g->get('https://gis.nature.cz/arcgis/rest/services/UzemniOchrana/ChranUzemi/MapServer/0/',
          'maloplosne');

?>