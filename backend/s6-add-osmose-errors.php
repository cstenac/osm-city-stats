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
  if ($bbox) { 
    $count = get_one_data("SELECT count(relation_id) as count from city_geom WHERE geom_dump &&  $bbox_query", "count");
  } else {
# Not yet incremental
    $count = get_one_data("SELECT count(relation_id) as count from city_geom", "count");
  }
  echo "Obtained target admin count: $count\n";

  if ($bbox) {
    $query = "SELECT relation_id as id, city as name from city_geom WHERE geom_dump && $bbox_query";
  } else {
# Not yet incremental
    $query = "SELECT relation_id as id, city as name from city_geom";
  }



  $result = pg_query($query);
  $loop_index = 0;
//  pg_query("begin");
  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    $loop_index++;
    
    echo "Computing errors for $name ($id) (progress: ".($loop_index-1)."/$count)\n";
  
    // Compute numbers of l1, l2, l3 errors in city
    $l1 = get_one_data("SELECT COUNT(*) as count from osmose_event oe, city_geom cg WHERE ST_Within(oe.point, cg.geom_dump) AND oe.level = 1 AND cg.relation_id=$id", "count");
    $l2 = get_one_data("SELECT COUNT(*) as count from osmose_event oe, city_geom cg WHERE ST_Within(oe.point, cg.geom_dump) AND oe.level = 2 AND cg.relation_id=$id", "count");
    $l3 = get_one_data("SELECT COUNT(*) as count from osmose_event oe, city_geom cg WHERE ST_Within(oe.point, cg.geom_dump) AND oe.level = 3 AND cg.relation_id=$id", "count");

    $finq =  "UPDATE city_data set l1_errors = $l1, l2_errors=$l2, l3_errors=$l3 where relation_id = $id";
    echo "   $finq\n";
    pg_query($finq);
  }
?>
