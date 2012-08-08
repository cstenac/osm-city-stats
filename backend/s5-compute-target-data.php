<?php
  /**
   * Step 4 of the analysis: compute the ways per target admin level.
   */
  include("../config/config.php.inc");
  include("lib/sighandler.inc.php");
  include("lib/timeutils.inc.php");
  include("lib/dbutils.inc.php");

  if ($argc < 2) {
    echo "MODE required\n";
    die;
  }

  $mode = $argv[1];
  if ($mode == "relation") {
    $bbox = true;
    $bbox_query = "(SELECT geom as polygon from region_geom where relation_id = ".$argv[2].")";
  } else if ($mode == "bbox") {
    $bbox = true;
    $bbox_query = $argv[2];
  } else {
    $bbox = false;
  }
 
  connect($db_conn_string, $db_search_path);
   if ($bbox) { 
    $count = get_one_data("SELECT count(relation_id) as count from city_geom WHERE geom_dump &&  $bbox_query", "count");
  } else {
    $count = get_one_data("SELECT count(relation_id) as count from city_geom where needs_compute=1", "count");
  }
  echo "Obtained target admin count: $count\n";

  if ($bbox) {
    $query = "SELECT relation_id as id, city as name from city_geom WHERE geom_dump && $bbox_query";
  } else {
    $query = "SELECT relation_id as id, city as name from city_geom where needs_compute=1";
  }



  $result = pg_query($query);
  $loop_index = 0;
//  pg_query("begin");
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    $loop_index++;
    /*
    if ($loop_index++ % $modulo != $selected_modulo) {
        echo "Ignoring $name ($id) (modulo)\n";
        continue;
    }
    */
    # Start by cleaning our own data to make this script replayable
    pg_query("DELETE FROM city_data where relation_id=$id");
    
    echo "Computing stats for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start("stats");
    /* Optional */
    $insee = get_one_data("SELECT tags -> 'ref:INSEE' as insee FROM relations where id=$id", "insee");
    $pop = get_one_data("SELECT population from dbpedia_city where insee='$insee'", "population");
    $maire = get_one_data("SELECT maire from dbpedia_city where insee='$insee'", "maire");
    $maire = str_replace("'", " ", $maire);
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

    $finq = "INSERT INTO city_data(relation_id, area, highway_length, highway_count, residential_highway_length, residential_highway_count, building_count, building_area, residential_count, residential_area, places, townhalls, schools, pows, insee, population, maire) VALUES($id, $area, $hwl, $hwc, $rhwl, $rhwc, $bc, $ba, $rc, $ra, $place, $townhall,$school, $pow, '$insee', $pop, '$maire')";
    echo " $finq\n";
    pg_query($finq);
    time_end("stats");
  }
//  pg_query("commit");

?>
