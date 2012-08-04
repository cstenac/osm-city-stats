<?php
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
?>
