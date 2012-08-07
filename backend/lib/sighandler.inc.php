<?php
  declare(ticks=1);
  function sighandler($signo) {
    global $conn;
    echo "SIGNAL CAUGHT, ABORTING QUERY\n";
    pg_cancel_query($conn);
    exit;
  }
  echo "Installing signal handlers\n";
  pcntl_signal(SIGINT, "sighandler");
  pcntl_signal(SIGTERM, "sighandler");

?>
