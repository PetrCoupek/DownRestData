#Down Rest Data

Command line PHP script for download spatial data from the ArcGIS rest API server
final GeoJSON could be imported into QGIS using the following approach: Menu Layer -> add vector layer -> JSON  file -> button Add  .

## class construct parameters
 *  @param $datasource  - URL cesta k mapove sluzbe zakoncena /n/ , kde n je cislo vrstvy
 *  @param $filename - jmeno vystupniho souboru JSON bez koncovky
 *  @param $limit - maximalni pocat najednou stazitelnych zaznamu (je omezovano )