<?php
  /**
   * Step 3 of the analysis: compute the polygon of each city from the administrative
   * boundaries in the DB.
   *
   * We do it city-by-cituy for incrementality purposes
   */

  include("../config/config.php.inc");
  include("lib/sighandler.inc.php");
  include("lib/timeutils.inc.php");
  include("lib/dbutils.inc.php");
  
  connect($db_conn_string, $db_search_path);
 // safe_dml_query("DELETE FROM city_geom");
/*
  safe_dml_query("DROP TABLE IF EXISTS city_geom");
  safe_dml_query("CREATE TABLE city_geom(".
      "relation_id INTEGER PRIMARY KEY, ".
      "city VARCHAR(200), ".
      " geog geography('POLYGON', 4326)".
      ")");
  safe_dml_query("SELECT AddGeometryColumn('city_geom', 'geom', 4326, 'GEOMETRY', 2)");
  safe_dml_query("SELECT AddGeometryColumn('city_geom', 'geom_dump', 4326, 'POLYGON', 2)");
*/
    /* Compute a polygon using flat geometry */ 
    /*
    safe_dml_query("INSERT INTO city_geom(relation_id, city, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(way_geometry.geom) geom ".
	"FROM way_geometry ".
	"	INNER JOIN ways on ways.id=way_geometry.way_id ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' GROUP BY r.id;");
  } else {
	  */
    $result = pg_query("SELECT id from relations r where r.tags -> 'admin_level' = '8'") or die("Query failed");
    $beg = microtime(true);
    $count = 0;
    $errors = Array();
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
      $id = $line["id"];
      $t = round( (microtime(true) - $beg) * 1000);
      echo "City $id ($count - $t ms total - ".count($errors)." errors)\n";
 	$count++;
/*
	if ($id == 42288) continue; // Unknown geometry type 0
	if ($id == 70133) continue; // EXCEPTION IN LWSGEOM
	if ($id == 75862) continue; // EXCEPTION IN LWSGEOM
	if ($id == 116834) continue; // EXCEPTION IN LWSGEOM
	if ($id == 270537) continue; // Geom type 0
	if ($id == 342898) continue; // OOM
	if ($id == 405621) continue; // Geom type 0
	if ($id == 452987) continue; // lwsgeom
	if ($id == 452997) continue; // lwsgeom
*/

	safe_dml_query("DELETE FROM city_geom where relation_id=$id");

        if (!$has_linestring_in_ways) {
	    $ret = dml_query("INSERT INTO city_geom(relation_id, city, geom) SELECT r.id, MIN(hstore(r.tags) -> 'name'),".
	                   " ST_Polygonize(way_geometry.geom) geom FROM way_geometry ".
		           " INNER JOIN relation_members rn on rn.member_id = way_geometry.way_id ".
			   " INNER JOIN relations r on rn.relation_id = r.id ".
			   " WHERE rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' AND r.id=$id GROUP BY r.id");
    	} else {
       $ret = dml_query("INSERT INTO city_geom(relation_id, city, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(ways.linestring) geom ".
        "FROM ways ".
        "        INNER JOIN relation_members rn on rn.member_id = ways.id ".
        "        INNER JOIN relations r  on rn.relation_id = r.id WHERE r.id=$id GROUP BY r.id");
    	}
    
	if (!$ret) {
		$errors[$id] = pg_last_error();
	}
    }
	foreach ($errors as $id => $err) {
		echo "Failed city $id because of $err\n";
	}

    /* Compute a polygon using flat geometry */ 
    /*safe_dml_query("INSERT INTO city_geom(relation_id, city, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(ways.linestring) geom ".
	"FROM ways ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' GROUP BY r.id;");
	*/
  //}

   safe_dml_query("   UPDATE city_geom SET geom_dump = (ST_Dump(geom)).geom;");
  /* Recompute the polygon of each city as a geography. This 
-- will be used for precise computations like city surface */
  safe_dml_query("update city_geom set geog = (ST_Dump(geom)).geom;");

/*
  // Also create geometry for regions. This can help us focus more the analysis 
  safe_dml_query("DROP TABLE IF EXISTS region_geom;");
  safe_dml_query("CREATE TABLE region_geom(" .
  "  relation_id INTEGER PRIMARY KEY, ".
  "  geog geography('POLYGON', 4326))");
  safe_dml_query("SELECT AddGeometryColumn('region_geom', 'geom', 4326, 'GEOMETRY', 2);");
  safe_dml_query("SELECT AddGeometryColumn('region_geom', 'geom_dump', 4326, 'POLYGON', 2);");

  if (!$has_linestring_in_ways) {
    // Compute a polygon using flat geometry
    safe_dml_query("INSERT INTO region_geom(relation_id,  geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(way_geometry.geom) geom ".
	"FROM way_geometry ".
	"	INNER JOIN ways on ways.id=way_geometry.way_id ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;");
  } else {
    // Compute a polygon using flat geometry 
    safe_dml_query("INSERT INTO region_geom(relation_id, geom) ".
    "SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(ways.linestring) geom ".
	"FROM ways ".
	"        INNER JOIN relation_members rn on rn.member_id = ways.id ".
	"        INNER JOIN relations r on rn.relation_id = r.id ".
    "            AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;");
  }
  safe_dml_query("UPDATE region_geom SET geom_dump = (ST_Dump(geom)).geom;");
  safe_dml_query("update region_geom set geog = (ST_Dump(geom)).geom;");
*/
?>
