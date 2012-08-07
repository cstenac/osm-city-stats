<?php
  /**
   * Bootstrap 2. Precompute the polygon of the higher admin regions
   */
  
  include("lib/sighandler.inc.php");
  include("lib/timeutils.inc.php");
  include("lib/dbutils.inc.php");
  include("../config/config.php.inc");
  connect($db_conn_string, $db_search_path);
  
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
    "SELECT r.id, ST_Polygonize(way_geometry.geom) geom ".
	"FROM way_geometry ".
	"	INNER JOIN ways on ways.id=way_geometry.way_id ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;");
  } else {
    $result = pg_query("SELECT id from relations r where r.tags -> 'admin_level' = '4'") or die("Query failed");
    $beg = microtime(true);
    $count = 0;
    $errors = Array();
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      $id = $line["id"];
      $t = round( (microtime(true) - $beg) * 1000);
      echo "City $id ($count - $t ms total - ".count($errors)." errors)\n";
     	$count++;
       $ret = dml_query("INSERT INTO region_geom(relation_id, geom) ".
    "SELECT r.id, ST_Buffer( ST_Polygonize(ways.linestring), 0.0) geom ".
        "FROM ways ".
        "        INNER JOIN relation_members rn on rn.member_id = ways.id ".
        "        INNER JOIN relations r  on rn.relation_id = r.id WHERE r.id=$id GROUP BY r.id");
    
	if (!$ret) {
		$errors[$id] = pg_last_error();
	}
	}
	foreach ($errors as $id => $err) {
		echo "Failed region $id because of $err\n";
	}

    /* Compute a polygon using flat geometry */ 
    /*safe_dml_query("INSERT INTO city_geom(relation_id, city, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(ways.linestring) geom ".
	"FROM ways ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' GROUP BY r.id;");
	*/

    /* Compute a polygon using flat geometry */ 
    /*
    safe_dml_query("INSERT INTO region_geom(relation_id, geom) ".
    "SELECT r.id, ST_Buffer(ST_Polygonize(ways.linestring), 0.0) geom ".
	"FROM ways ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;");
    */
  }
  safe_dml_query("UPDATE region_geom SET geom_dump = (ST_Dump(geom)).geom;");
  safe_dml_query("update region_geom set geog = (ST_Dump(geom)).geom;");

?>
