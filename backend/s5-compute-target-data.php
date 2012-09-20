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

  function count_tag($id, $isNode, $isWay, $isCW, $tagExpr) {
    $count = 0;
    if ($isNode ){
      $count += get_one_data("SELECT COUNT(*) AS count FROM city_node where relation_id=$id and $tagExpr", "count");
    }
    if ($isWay){
      $count += get_one_data("SELECT COUNT(*) AS count FROM city_way where relation_id=$id and $tagExpr", "count");
    }
     if ($isCW){
      $count += get_one_data("SELECT COUNT(*) AS count FROM city_closedway where relation_id=$id and $tagExpr", "count");
    }
    return $count;
  }

  function compute_hw_data($id, $head_tag) {
    $query = "INSERT INTO city_w_data (relation_id, type, count, length, total_length) SELECT $id, ".
    	    " '$head_tag:' || (tags -> '$head_tag'), COUNT(*), SUM(ST_Length(ST_Intersection(city_way.geog, cg.geog))), SUM(ST_Length(city_way.geog)) ".
    	     "FROM city_way INNER JOIN city_geom cg on cg.relation_id = city_way.relation_id WHERE city_way.relation_id=$id ".
	     "AND tags ? '$head_tag' GROUP BY tags -> '$head_tag' ";
    $result = pg_query($query);
    /*
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
	print_r($line);
    }*/
  }

  function compute_a_data($id, $head_tag) {
    $query = "INSERT INTO city_a_data (relation_id, type, count, area, total_area) SELECT $id, ".
    	    " '$head_tag:' || (tags -> '$head_tag'), COUNT(*), SUM(ST_Area(ST_Intersection(city_closedway.geog, cg.geog))), SUM(ST_Area(city_closedway.geog)) ".
    	     "FROM city_closedway INNER JOIN city_geom cg on cg.relation_id = city_closedway.relation_id WHERE city_closedway.relation_id=$id ".
	     "AND tags ? '$head_tag' GROUP BY tags -> '$head_tag' ";
    $result = pg_query($query);
  }
  function compute_n_data($id, $head_tag) {
    $query = "INSERT INTO city_n_data (relation_id, type, count) SELECT $id, ".
    	    " '$head_tag:' || (tags -> '$head_tag'), COUNT(*) ".
    	     "FROM city_node INNER JOIN city_geom cg on cg.relation_id = city_node.relation_id WHERE city_node.relation_id=$id ".
	     "AND tags ? '$head_tag' GROUP BY tags -> '$head_tag' ";
    $result = pg_query($query);
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
    pg_query("DELETE FROM city_w_data where relation_id=$id");
    pg_query("DELETE FROM city_a_data where relation_id=$id");
    
    echo "Computing stats for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start("stats");

    compute_hw_data($id, "highway");
    compute_hw_data($id, "railway");
    compute_hw_data($id, "cycleway");
    compute_hw_data($id, "waterway");
    compute_hw_data($id, "power");
    
    compute_a_data($id, "landuse");
//    compute_a_data($id, "building");
    compute_a_data($id, "leisure");

    compute_n_data($id, "place");

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

    $place = count_tag($id, True, False, True, "tags ? 'place'");
    $townhall = count_tag($id, True, False, True, "tags -> 'amenity' = 'townhall'");
    $school = count_tag($id, True, False, True, "tags -> 'amenity' = 'school'");
    $pow = count_tag($id, True, False, True, "tags -> 'amenity' = 'place_of_worship'");

    $shops = count_tag($id, True, False, True, "tags ? 'shop'");
    $offices = count_tag($id, True, False, True, "tags ? 'office'");
    $amenities = count_tag($id, True, False, True, "tags ? 'amenity'");
    $leisures = count_tag($id, True, False, True, "tags ? 'leisure'");
    $crafts = count_tag($id, True, False, True, "tags ? 'craft'");
    $emergencies = count_tag($id, True, False, True, "tags ? 'emergency'");
    $tourisms = count_tag($id, True, False, True, "tags ? 'tourism'");
    $historics = count_tag($id, True, False, True, "tags ? 'historic'");
    $militaries = count_tag($id, True, False, True, "tags ? 'military'");

    $finq = "INSERT INTO city_data(relation_id, area, highway_length, highway_count, ".
    	   "residential_highway_length, residential_highway_count, building_count, building_area, ".
	   "residential_count, residential_area, ".
	   "places, townhalls, schools, pows, shops, offices, amenities, leisures, ".
	   "crafts, emergencies, tourisms, historics, militaries, ".
	   "insee, population, maire) VALUES($id, $area, $hwl, $hwc, ".
	   "$rhwl, $rhwc, $bc, $ba, ".
	   "$rc, $ra, ".
	   "$place, $townhall,$school, $pow, $shops, $offices, $amenities, $leisures, ".
	   "$crafts, $emergencies, $tourisms, $historics, $militaries, ".
	   "'$insee', $pop, '$maire')";
    echo " $finq\n";
    pg_query($finq);
    time_end("stats");
  }
//  pg_query("commit");

?>
