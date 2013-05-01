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
  echo $mode;
  if ($mode == "relation") {
    $bbox = true;
    $bbox_query = "(SELECT geom as polygon from admin_geom where relation_id = ".$argv[2].")";
  } else if ($mode == "bbox") {
    $bbox = true;
    $bbox_query = $argv[2];
  } else {
    $bbox = false;
  }
  echo "bbox $bbox";
 
  connect($db_conn_string, $db_search_path);

  if ($bbox) { 
    $count = get_one_data("SELECT count(relation_id) as count from admin_geom WHERE tags->'admin_level' = '8' and geom_dump &&  $bbox_query", "count");
  } else {
    $count = get_one_data("SELECT count(relation_id) as count from city_geom where needs_compute=1", "count");
  }
  echo "Obtained target admin count: $count\n";

  if ($bbox) {
    $query = "SELECT relation_id as id, name from admin_geom WHERE tags->'admin_level' = '8' and geom_dump && $bbox_query";
  } else {
    $query = "SELECT relation_id as id, city as name from city_geom where needs_compute=1";
  }

  //pg_query("BEGIN");
  $result = pg_query($query);
  $loop_index = 0;
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $loop_index++;
    $id = $line["id"];
    $name = $line["name"];
    # Start by cleaning our own data to make this script replayable
    pg_query("DELETE FROM city_way where relation_id=$id");
    echo "Computing city ways for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start();
    if ($has_linestring_in_ways) {
        dml_query("INSERT INTO city_way SELECT $id, ways.id, ways.tags, ways.linestring from ways INNER JOIN city_geom cg ON ST_Intersects(cg.geom_dump, ways.linestring) WHERE cg.relation_id=$id");
    } else {
        dml_query("INSERT INTO city_way SELECT $id, wg.way_id, wg.tags,  wg.geom from way_geometry wg INNER JOIN city_geom cg ON ST_Intersects(cg.geom_dump, wg.geom) WHERE cg.relation_id=$id");
    }
    time_end("ways");
  }
//  pg_query("COMMIT");
?>
