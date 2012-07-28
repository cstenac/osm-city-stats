<?php
  /**
   * Step 5 of the analysis: compute the ways and nodes per city, and compute
   *  the aggregated city data
   */

  $modulo = 1;
  $selected_modulo = 0;

  if ($argc >= 3) {
    $modulo = $argv[1];
    $selected_modulo = $argv[2];
  }

  /* ******************** Utilities ****************** */

  $time_start = 0;
  function time_start()  {
    global $time_start;
    $time_start = microtime(true);
  }

  function time_end($name) {
    global $time_start;
    $time_end = microtime(true);
    $t = round(($time_end-$time_start)*1000);
    echo "    $name in $t ms\n";
  }

  function get_one_data($query, $name) {
    $res = pg_query($query);
    $array = pg_fetch_array($res);
    $data = $array[$name];
    if (!isset($data)) {
        $data = "0";
    }
    return $data;
  }


  /* ******************** Main ****************** */

  include("../config/config.php.inc");

  $conn = pg_connect($db_conn_string) or die('Could not connect: ' . pg_last_error());


  $query = "SELECT id, tags -> 'name' as name from relations WHERE tags -> 'admin_level' = '8'";
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
    echo "Working for $name ($id)\n";
    pg_query("DELETE FROM city_way where relation_id=$id");
    pg_query("DELETE FROM city_node where relation_id=$id");
    pg_query("DELETE FROM city_closedway where relation_id=$id");
    pg_query("DELETE FROM city_data where relation_id=$id");
    
    // Compute ways in city
    echo " Computing ways\n";time_start();
    pg_query("INSERT INTO city_way SELECT $id,w.id, w.tags, wg.geom from ways w, city_geom cg, way_geometry wg WHERE wg.way_id = w.id AND ST_Intersects(cg.geom_dump, wg.geom) AND cg.relation_id=$id");
    time_end("ways"); time_start();
    echo " Computing nodes\n";
    pg_query("INSERT INTO city_node SELECT $id,n.id, n.tags, n.geom from nodes n, city_geom cg WHERE ST_Intersects( cg.geom_dump, n.geom) AND cg.relation_id=$id AND array_length(akeys(n.tags), 1) > 0");
    time_end("nodes"); time_start();
    echo " Computing closed ways \n";
    pg_query("INSERT INTO city_closedway SELECT $id, w.id, w.tags, cwg.geom from ways w,city_geom cg, closedway_geometry cwg   WHERE cwg.way_id = w.id AND ST_Intersects(cg.geom_dump, cwg.geom ) AND cg.relation_id=$id");
    time_end("closedways");


    echo " Computing stats\n";
    time_start("stats");
    /* Optional */
    $insee = get_one_data("SELECT tags -> 'ref:INSEE' as insee FROM relations where id=$id", "insee");
    $pop = get_one_data("SELECT population from dbpedia_city where insee='$insee'", "population");
    $maire = get_one_data("SELECT maire from dbpedia_city where insee='$insee'", "maire");
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
