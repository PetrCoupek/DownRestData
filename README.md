#Down Rest Data

Command line PHP script for download spatial data from the ArcGIS rest API server.

Final GeoJSON could be imported into QGIS using the following approach: Menu Layer -> add vector layer -> JSON  file -> button Add  .

## class construct parameters
 *  @param $datasource  - URL path to the ArcGIS mapservice ended wuth /n/ , where n is the layer number
 *  @param $filename - name of the output JSON datafile, without .json postfix
 *  @param $limit - the maximum of features to be requested in one step / GET request. This is server side limitation for downoading large amout of data at once. It differs amout server instalations. Normally, less than 1000 is set.