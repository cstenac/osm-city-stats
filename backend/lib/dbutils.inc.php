<?php
  $conn = null;
  function connect($db_conn_string, $db_search_path) {
      global $conn;
    $conn = pg_connect($db_conn_string) or die('Could not connect: ' . pg_last_error());
    pg_query("SET search_path TO $db_search_path");
    return $conn;
  }

  function safe_dml_query($query) {
      global $conn;
    echo "------------------------\n";
    echo "Executing PG query: $query\n";
    $time_start = microtime(true);
    pg_send_query($conn, $query) or die ("Failed to execute query $query");
    while (pg_connection_busy($conn)) {
        if (microtime(true) - $time_start > 30) {
            if (rand(0, 10) == 0) {
                echo "Busy for ".round((microtime(true)-$time_start)*1000)." ms -";
            }
            sleep(5);
        }
        usleep(2000);
    }
    $res = pg_get_result($conn);
    if (pg_result_error($res) != null) {
        die("Error during query: ".pg_result_error($res)."\n");
    }
    $time_end = microtime(true);
    $rows = pg_affected_rows($res);
    echo "Done executing $query: $rows touched\n";
    $t = round(($time_end-$time_start)*1000);
    echo "Query time: $t ms\n";
    echo "------------------------\n";
  }
  function dml_query($query) {
    echo "------------------------\n";
    echo "Executing PG query: $query\n";
    $time_start = microtime(true);
    $res = pg_query($query);
    $time_end = microtime(true);
    echo "Done executing $query";
    $t = round(($time_end-$time_start)*1000);
    echo "Query time: $t ms\n";
    echo "------------------------\n";
    return $res;
  }

?>
