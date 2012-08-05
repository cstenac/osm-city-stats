<?php
  /**
   * Step 5 of the analysis: compute the ways and nodes per city.
   * We iterate 3 times on the cities, to have better index locality
   * on the nodes, way_geometry and closedway_geometry indexes
   */

  $modulo = 1;
  $selected_modulo = 0;

  if ($argc >= 3) {
    $modulo = $argv[1];
    $selected_modulo = $argv[2];
  }

  include("timeutils.php");
  include("dbutils.php");
  include("../config/config.php.inc");
  
  connect($db_conn_string, $db_search_path);

  $count = get_one_data("SELECT count(id) as count from  relations WHERE tags -> 'admin_level' = '8'", "count");


  $query = "SELECT id, tags -> 'name' as name from relations WHERE tags -> 'admin_level' = '8'";



  /* First loop: city_way */
  pg_query("BEGIN");
  $result = pg_query($query);
  $loop_index = 0;
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    if ($loop_index++ % $modulo != $selected_modulo) {
        echo "Ignoring $name ($id) (modulo)\n";
        continue;
    }
    # Start by cleaning our own data to make this script replayable
    pg_query("DELETE FROM city_way where relation_id=$id");
    echo "Computing city ways for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start();
    if ($has_linestring_in_ways) {
        safe_dml_query("INSERT INTO city_way SELECT $id,w.id, w.tags,  w.linestring from ways w, city_geom cg WHERE ST_Intersects(cg.geom_dump, w.linestring) AND cg.relation_id=$id AND ST_NumPoints(w.linestring) > 1");
    } else {
        safe_dml_query("INSERT INTO city_way SELECT $id,w.id, w.tags,  wg.geom from ways w, city_geom cg, way_geometry wg WHERE wg.way_id = w.id AND ST_Intersects(cg.geom_dump, wg.geom) AND cg.relation_id=$id");
    }
    time_end("ways");
  }
  pg_query("COMMIT");

  /* Second loop: city_closedway */
  $result = pg_query($query);
  $loop_index = 0;
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    if ($loop_index++ % $modulo != $selected_modulo) {
        echo "Ignoring $name ($id) (modulo)\n";
        continue;
    }
    # Start by cleaning our own data to make this script replayable
    pg_query("DELETE FROM city_closedway where relation_id=$id");
    echo "Computing city closed ways for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start();
    pg_query("INSERT INTO city_closedway SELECT $id, w.id, w.tags, cwg.geom from ways w,city_geom cg, closedway_geometry cwg   WHERE cwg.way_id = w.id AND ST_Intersects(cg.geom_dump, cwg.geom ) AND cg.relation_id=$id");
    time_end("closed ways");
  }

  /* Third loop: city_nodes */
  $result = pg_query($query);
  $loop_index = 0;
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    if ($loop_index++ % $modulo != $selected_modulo) {
        echo "Ignoring $name ($id) (modulo)\n";
        continue;
    }
    # Start by cleaning our own data to make this script replayable
    pg_query("DELETE FROM city_node where relation_id=$id");
    echo "Computing city nodes for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start();
    pg_query("INSERT INTO city_node SELECT $id,n.id, n.tags, n.geom from nodes n, city_geom cg WHERE ST_Intersects( cg.geom_dump, n.geom) AND cg.relation_id=$id AND array_length(akeys(n.tags), 1) > 0");
    time_end("nodes");
  }

  /* Fourth loop: data */
  $result = pg_query($query);
  $loop_index = 0;
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    if ($loop_index++ % $modulo != $selected_modulo) {
        echo "Ignoring $name ($id) (modulo)\n";
        continue;
    }
    # Start by cleaning our own data to make this script replayable
    pg_query("DELETE FROM city_data where relation_id=$id");
    
    echo "Computing stats for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start("stats");
    /* Optional */
    $insee = get_one_data("SELECT tags -> 'ref:INSEE' as insee FROM relations where id=$id", "insee");
    $pop = get_one_data("SELECT population from dbpedia_city where insee='$insee'", "population");
    $maire = et_one_data("SELECT maire from dbpedia_city where insee='$insee'", "maire");
    /* End optional */


    $area = get_one_data("SELECT ST_Area(geog) as area FROM city_geom where relation_id=$id", "area");
    $hwl = get_one_data("SELECT SUM(ST_Length(geog)) as length FROM city_way where relation_id=$id and tags ? 'highway'", "length");
    $hwc = get_one_data("SELECT COUNT(*) as count FROM city_way where relation_id=$id and tags ? 'highway'", "count");
    $rhwl = get_one_data("SELECT SUM(ST_Length(geog)) as length FROM city_way where relation_id=$id and tags -> 'highway' = 'residential'", "length");
    $rhwc = get_one_data("SELECT COUNT(*) as count FROM city_way where relation_id=$id and tags -> 'highway' = 'residential'", "count");
  
    $bc = get_one_data("SELECT COUNT(*) as count FROM city_way where relation_id=$id and tags -> 'building' = 'yes'", "count");
    $ba = get_one_data("SELECT SUM(ST_Area(geog)) as area FROM city_closedway where relation_id=$id and tags -> 'building' = 'yes'", "area"); if (!isset($ba)) $ba = 0;
    $rc = get_one_data("SELECT COUNT(*) as count FROM city_way where relation_id=$id and tags -> 'landuse' = 'residential'", "count");
    $ra = get_one_data("SELECT SUM(ST_Area(geog)) as area FROM city_closedway where relation_id=$id and tags -> 'landuse' = 'residential'", "area"); if (!isset($ra)) $ra = 0;

    $place = get_one_data("SELECT COUNT(*) AS count FROM city_closedway where relation_id=$id and tags ? 'place'", "count");
    $place += get_one_data("SELECT COUNT(*) AS count FROM city_node where relation_id=$id and tags ? 'place' ", "count");

    $townhall = get_one_data("SELECT COUNT(*) AS count FROM city_closedway where relation_id=$id and tags -> 'amenity' = 'townhall'", "count");
    $townhall += get_one_data("SELECT COUNT(*) AS count FROM city_node where relation_id=$id and tags -> 'amenity' = 'townhall'", "count");

    $school = get_one_data("SELECT COUNT(*) AS count FROM city_closedway where relation_id=$id and tags -> 'amenity' = 'school'", "count");
    $school += get_one_data("SELECT COUNT(*) AS count FROM city_node where relation_id=$id and tags -> 'amenity' = 'school'", "count");

    $pow = get_one_data("SELECT COUNT(*) AS count FROM city_closedway where relation_id=$id and tags -> 'amenity' = 'place_of_worship'", "count");
    $pow += get_one_data("SELECT COUNT(*) AS count FROM city_node where relation_id=$id and tags -> 'amenity' = 'place_of_worship'", "count");

    $finq =  "INSERT INTO city_data(relation_id, area, highway_length, highway_count, residential_highway_length, residential_highway_count, building_count, building_area, residential_count, residential_area, places, townhalls, schools, pows, insee, population, maire) VALUES($id, $area, $hwl, $hwc, $rhwl, $rhwc, $bc, $ba, $rc, $ra, $place, $townhall,$school, $pow, '$insee', $pop, '$maire')";
    echo "  $finq\n";
    pg_query($finq);
    time_end("stats");
  }
?>
