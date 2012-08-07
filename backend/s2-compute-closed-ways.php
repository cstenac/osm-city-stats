<?php
  /**
   * Step 2 of the analysis: compute the polygons for closed ways
   * We use the previously exported way data
   * inestrings and polygons 
   * for all cities.
   *
   * We don't always need to do it, if the db already has interesting data
   */

  include("../config/config.php.inc");
  include("lib/sighandler.inc.php");
  include("lib/timeutils.inc.php");
  include("lib/dbutils.inc.php");

  connect($db_conn_string, $db_search_path);

  /* Create a table of closed ways geometries */
  safe_dml_query("DROP TABLE IF EXISTS closedway_geometry");
  safe_dml_query("CREATE TABLE closedway_geometry(way_id bigint NOT NULL)");
  safe_dml_query("SELECT AddGeometryColumn('', 'closedway_geometry', 'geom', 4326, 'GEOMETRY', 2)");
  safe_dml_query("INSERT INTO closedway_geometry SELECT way_id, ST_MakePolygon(geom) ".
        "FROM way_geometry ".
        "  WHERE ST_IsClosed(geom) AND ST_NumPoints(geom) >= 4");
  /* Create index on closedway_geometry */
  safe_dml_query("CREATE INDEX idx_closedway_geometry_way_id ON closedway_geometry USING btree (way_id)");
  safe_dml_query("CREATE INDEX idx_closedway_geometry_geom ON closedway_geometry USING gist (geom)");

?>
