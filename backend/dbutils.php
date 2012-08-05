<?php
  function connect($db_conn_string, $db_search_path) {
    $conn = pg_connect($db_conn_string) or die('Could not connect: ' . pg_last_error());
    pg_query("SET search_path TO $db_search_path");
    return $conn;
  }

  function safe_dml_query($query) {
    echo "------------------------\n";
    echo "Executing PG query: $query\n";
    $time_start = microtime(true);
    $res = pg_query($query) or die ("Failed to execute query $query");
    $time_end = microtime(true);
    $rows = pg_num_rows($res);
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
