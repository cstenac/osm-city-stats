<?php
  /**
   * Step 3 of the analysis: compute the polygon of each city from the administrative
   * boundaries in the DB
   */
  
  include("timeutils.php");
  include("dbutils.php");
  include("../config/config.php.inc");

  $conn = pg_connect($db_conn_string) or die('Could not connect: ' . pg_last_error());

  safe_dml_query("DROP TABLE IF EXISTS city_geom");
  safe_dml_query("CREATE TABLE city_geom(".
      "relation_id INTEGER PRIMARY KEY, ".
      "city VARCHAR(200), ".
      " geog geography('POLYGON', 4326)".
      ")");
  safe_dml_query("SELECT AddGeometryColumn('city_geom', 'geom', 4326, 'GEOMETRY', 2)");
  safe_dml_query("SELECT AddGeometryColumn('city_geom', 'geom_dump', 4326, 'POLYGON', 2)");

  if (!$has_linestring_in_ways) {
    /* Compute a polygon using flat geometry */ 
    safe_dml_query("INSERT INTO city_geom(relation_id, city, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(way_geometry.geom) geom ".
	"FROM way_geometry ".
	"	INNER JOIN ways on ways.id=way_geometry.way_id ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' GROUP BY r.id;");
  } else {
    /* Compute a polygon using flat geometry */ 
    safe_dml_query("INSERT INTO city_geom(relation_id, city, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(ways.linestring) geom ".
	"FROM ways ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' GROUP BY r.id;");
  }

   safe_dml_query("   UPDATE city_geom SET geom_dump = (ST_Dump(geom)).geom;");
  /* Recompute the polygon of each city as a geography. This 
-- will be used for precise computations like city surface */
  safe_dml_query("update city_geom set geog = (ST_Dump(geom)).geom;");

  /* Also create geometry for regions. This can help us focus more the analysis */
  safe_dml_query("DROP TABLE IF EXISTS region_geom;");
  safe_dml_query("CREATE TABLE region_geom(" .
  "  relation_id INTEGER PRIMARY KEY, ".
  "  geog geography('POLYGON', 4326))");
  safe_dml_query("SELECT AddGeometryColumn('region_geom', 'geom', 4326, 'GEOMETRY', 2);");
  safe_dml_query("SELECT AddGeometryColumn('region_geom', 'geom_dump', 4326, 'POLYGON', 2);");

  if (!$has_linestring_in_ways) {
    /* Compute a polygon using flat geometry */ 
    safe_dml_query("INSERT INTO region_geom(relation_id,  geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(way_geometry.geom) geom ".
	"FROM way_geometry ".
	"	INNER JOIN ways on ways.id=way_geometry.way_id ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;");
  } else {
    /* Compute a polygon using flat geometry */ 
    safe_dml_query("INSERT INTO region_geom(relation_id, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(ways.linestring) geom ".
	"FROM ways ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;");
  }
  safe_dml_query("UPDATE region_geom SET geom_dump = (ST_Dump(geom)).geom;");
  safe_dml_query("update region_geom set geog = (ST_Dump(geom)).geom;");

?>
