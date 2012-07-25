--- vim: ts=2

--- First step of the analysis:
---   We compute a geometrical LINESTRING for each way in the OSM database

--- Crete the line for each way
--- This table is only used for intersections, so keep it as geometry

DROP TABLE IF EXISTS way_geometry;
CREATE TABLE way_geometry(
  way_id bigint NOT NULL
);
SELECT AddGeometryColumn('', 'way_geometry', 'geom', 4326, 'GEOMETRY', 2);

DROP TABLE IF EXISTS closedway_geometry;
CREATE TABLE closedway_geometry(
  way_id bigint NOT NULL
);
SELECT AddGeometryColumn('', 'closedway_geometry', 'geom', 4326, 'GEOMETRY', 2);

-- add a linestring for every way (create a polyline)
INSERT INTO way_geometry select id, ( select ST_LineFromMultiPoint( Collect(nodes.geom) ) from nodes
left join way_nodes on nodes.id=way_nodes.node_id where way_nodes.way_id=ways.id ) FROM ways;

-- after creating a line for every way (polyline), we want closed ways to be stored as polygones.
-- now we need to add the polyline geometry for every closed way
INSERT INTO closedway_geometry SELECT ways.id,
 ( SELECT ST_MakePolygon( ST_LineFromMultiPoint(Collect(nodes.geom)) ) FROM nodes
  LEFT JOIN way_nodes ON nodes.id=way_nodes.node_id WHERE way_nodes.way_id=ways.id                                                              )
FROM ways
WHERE ST_IsClosed( (SELECT ST_LineFromMultiPoint( Collect(n.geom) ) FROM nodes n LEFT JOIN way_nodes wn ON n.id=wn.node_id WHERE ways.id=wn.way_id) )
AND ST_NumPoints( (SELECT ST_LineFromMultiPoint( Collect(n.geom) ) FROM nodes n LEFT JOIN way_nodes wn ON n.id=wn.node_id WHERE ways.id=wn.way_id) ) >= 3
;                                                                                                                                            

-- create index on way_geometry
CREATE INDEX idx_way_geometry_way_id ON way_geometry USING btree (way_id);
CREATE INDEX idx_way_geometry_geom ON way_geometry USING gist (geom);

CREATE INDEX idx_closedway_geometry_way_id ON closedway_geometry USING btree (way_id);
CREATE INDEX idx_closedway_geometry_geom ON closedway_geometry USING gist (geom);


