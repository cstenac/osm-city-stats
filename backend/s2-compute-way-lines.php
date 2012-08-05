<?php
  /**
   * Step 2 of the analysis: compute the linestrings and polygons 
   * for all cities.
   *
   * We don't always need to do it, if the db already has interesting data
   */
  
  include("timeutils.php");
  include("../config/config.php.inc");
  include("dbutils.php");

  connect($db_conn_string, $db_search_path);

  /* If we don't have it, compute a geometrical LINESTRING for each way in the OSM database */
  if (!$has_linestring_in_ways) {
    /* This table is only used for intersections, so keep it as geometry */
    safe_dml_query("DROP TABLE IF EXISTS way_geometry");
    safe_dml_query("CREATE TABLE way_geometry(" .
             " way_id bigint NOT NULL)");
    safe_dml_query("SELECT AddGeometryColumn('', 'way_geometry', 'geom', 4326, 'GEOMETRY', 2)");

    /*  add a linestring for every way (create a polyline) */
    safe_dml_query("INSERT INTO way_geometry select id, ".
        "( select ST_LineFromMultiPoint( Collect(nodes.geom) ) from nodes ".
        "left join way_nodes on nodes.id=way_nodes.node_id where way_nodes.way_id=ways.id ) FROM ways");
    safe_dml_query("CREATE INDEX idx_way_geometry_way_id ON way_geometry USING btree (way_id)");
    safe_dml_query("CREATE INDEX idx_way_geometry_geom ON way_geometry USING gist (geom)");
  }

  /* Create a table of closed ways geometries */
  safe_dml_query("DROP TABLE IF EXISTS closedway_geometry");
  safe_dml_query("CREATE TABLE closedway_geometry(way_id bigint NOT NULL)");
  safe_dml_query("SELECT AddGeometryColumn('', 'closedway_geometry', 'geom', 4326, 'GEOMETRY', 2)");
  if ($has_linestring_in_ways && $has_ispolygon_in_ways) {
    safe_dml_query("INSERT INTO closedway_geometry SELECT ways.id, ST_MakePolygon(ways.".$linestring_in_ways_col.
        ") FROM ways WHERE $ispolygon_in_ways_col = 't'");
  } else {
    safe_dml_query("INSERT INTO closedway_geometry SELECT ways.id,".
        "( SELECT ST_MakePolygon( ST_LineFromMultiPoint(Collect(nodes.geom)) ) FROM nodes ".
        "  LEFT JOIN way_nodes ON nodes.id=way_nodes.node_id WHERE way_nodes.way_id=ways.id ) ".
        "FROM ways ".
        "  WHERE ST_IsClosed( (SELECT geom from way_geometry WHERE way_id = ways.id)) " .
        "  AND ST_NumPoints( (SELECT geom from way_geometry WHERE way_id = ways.id)) >= 4");
  }
  /* Create index on closedway_geometry */
  safe_dml_query("CREATE INDEX idx_closedway_geometry_way_id ON closedway_geometry USING btree (way_id)");
  safe_dml_query("CREATE INDEX idx_closedway_geometry_geom ON closedway_geometry USING gist (geom)");

?>
