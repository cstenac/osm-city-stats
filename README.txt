This tool creates aggregated statistics about the content of the OpenStreetMap database, by administrative district.

For example "how many schools are there in this city ?"

It can be used both for information about the cities themselves, and to track the completion of the OSM database itself.


Requirements
============

 * A PostgreSQL 8.4 database engine with a database on which you have full rights
 * PostGIS and contrib extensions
 * Osmosis
 * PHP 5

First time install
==================

 * Have a database created with PLSQL support (See tools/create-db.sh)
 * Adapt config/config.sh.inc and config/config.php.inc to suit your installation

 "Standard OSM steps"
  * Enable PostGIS support on your database using tools/setup-osm-schema.sh
  * Import some OSM data using tools/populate-db-from-pbf.sh
     - From one pbf file: ./tools/populate-db-from-pbf.sh --read-pbf FILE
     - From multiple pbf files: ./tools/populate-db-from-pbf.sh --read-pbf FILE1 --read-pbf FILE2 --merge

Running the analysis
====================

 * Run backend/all.sh 

Re-running the analysis
=======================

 * Update the data in your database (either reimport or use various update methods)

Display the results
===================

 * Put web_ui/index.php somewhere it gets interpreted

TODO / Next steps
=================

 * (P1) Actually generate maps with the data
 * (P1) Ability to historize the data
 * (P2) Ability to have the analysis a bit more incremental
 * (P1) Performance improvements / Multithreading
