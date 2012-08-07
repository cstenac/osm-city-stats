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
  if ($mode = "relation") {
    $bbox = true;
    $bbox_query = "(SELECT geom as polygon from region_geom where relation_id = ".$argv[2].")";
  } else if ($mode = "bbox") {
    $bbox = true;
    $bbox_query = $argv[2];
  } else {
    $bbox = false;
  }
 
  connect($db_conn_string, $db_search_path);
 
  $count = get_one_data("SELECT count(relation_id) as count from city_geom WHERE geom_dump &&  $bbox_query", "count");
  echo "Obtained target admin count: $count\n";

  $query = "SELECT relation_id as id, city as name from city_geom WHERE geom_dump && $bbox_query";

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
    pg_query("DELETE FROM city_closedway where relation_id=$id");
    echo "Computing city closedways for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
    time_start();
    safe_dml_query("INSERT INTO city_closedway SELECT $id, cwg.way_id, wg.tags,  cwg.geom from closedway_geometry cwg INNER JOIN way_geometry wg on wg.way_id = cwg.way_id INNER JOIN city_geom cg ON ST_Intersects(cg.geom_dump, cwg.geom) WHERE cg.relation_id=$id");
    time_end("ways");
  }
  pg_query("COMMIT");
?>
