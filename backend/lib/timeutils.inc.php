<?php
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

?>
