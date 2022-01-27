
# Down Rest Data 

Command line PHP script for download ArcGIS server published vector spatial data from the dynamic ArcGIS REST service.
The script can be easily used for data mining public avalilable data sources. 

Final GeoJSON could be imported into QGIS using the following approach: Menu Layer -> add vector layer -> JSON  file -> button Add .

Available data sources can be found using spatial metadata catalogues.

## get mathod parameters
 *  **$datasource** *string* - URL path to the ArcGIS mapservice ended wuth /n/ , where n is the layer number

 *  **$filename** *string* - name of the output JSON datafile, without .json postfix

 *  **$limit** *int* - (optional) the maximum of features to be requested in one step / GET request. This is server side limitation for downoading large amout of data at once. It differs amout server instalations. Normally, less than 1000 is set.


 ## Example

 See, modify or run the example.php file from the command line using the command

```

 php -f example.php

``` 


```PHP

include_once "downrestdata.php";

  $g=new DownRestData();
  $g->get('https://gis.nature.cz/arcgis/rest/services/UzemniOchrana/ChranUzemi/MapServer/0/',
          'maloplosne');

``` 