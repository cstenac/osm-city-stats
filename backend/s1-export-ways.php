<?php
  /**
   * Step 1 of the analysis: export the interesting ways within the chosen bbox
   *
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
  /* This table is only used for intersections, so keep it as geometry */
  safe_dml_query("DROP TABLE IF EXISTS way_geometry");
  safe_dml_query("CREATE TABLE way_geometry(" .
           " way_id bigint NOT NULL, tags hstore)");
  safe_dml_query("SELECT AddGeometryColumn('', 'way_geometry', 'geom', 4326, 'GEOMETRY', 2)");

    if ($has_linestring_in_ways) {
        if ($bbox) {
            /* We already have linestring, only export the ways that we are interested in */
            safe_dml_query("INSERT INTO way_geometry SELECT id, tags, linestring from ways WHERE $bbox_query && ways.linestring AND ".
                "(ways.tags ? 'highway' OR ways.tags ? 'admin_level' OR ways.tags ? 'landuse' OR ways.tags ? 'building')");
        } else {
            safe_dml_query("INSERT INTO way_geometry SELECT id, tags, linestring from ways WHERE ".
                "(ways.tags ? 'highway' OR ways.tags ? 'admin_level' OR ways.tags ? 'landuse' OR ways.tags ? 'building')");
        }

    } else {
        /*  add a linestring for every way (create a polyline) */
        safe_dml_query("INSERT INTO way_geometry select id, ways.tags, ".
            "( select ST_LineFromMultiPoint( Collect(nodes.geom) ) from nodes ".
            "left join way_nodes on nodes.id=way_nodes.node_id where way_nodes.way_id=ways.id ) FROM ways");
    }
    safe_dml_query("CREATE INDEX idx_way_geometry_way_id ON way_geometry USING btree (way_id)");
    safe_dml_query("CREATE INDEX idx_way_geometry_geom ON way_geometry USING gist (geom)");
?>
