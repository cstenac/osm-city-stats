--- vim: ts=2

--- Analysis bootstrap:
---   Create a schema with one table for "nodes per city", one for "ways per city", and one for
---   "computed data per city"

DROP TABLE IF EXISTS city_geom;
create table city_geom (
  relation_id INTEGER,
  city TEXT,
	geog geography("POLYGON", 4326),
	needs_compute INTEGER
);
SELECT AddGeometryColumn('', 'city_geom', 'geom', 4326, 'GEOMETRY', 2);;
SELECT AddGeometryColumn('', 'city_geom', 'geom_dump', 4326, 'GEOMETRY', 2);;
CREATE INDEX idx_city_geom_geog ON city_geom using gist(geog);
CREATE INDEX idx_city_geom_geom_dump ON city_geom using gist(geom_dump);
CREATE INDEX idx_city_geom_relation_id ON city_geom using btree(relation_id);

-- Create list of ways for each city
-- Here, we recompute the geometry of each way as a geography for computations
DROP TABLE IF EXISTS city_way;
create table city_way (
	relation_id INTEGER ,
	way_id INTEGER,
	tags hstore ,
	geog geography("LINESTRING", 4326)	
	); 
CREATE INDEX idx_city_way_relation_id ON city_way(relation_id);

DROP TABLE IF EXISTS city_closedway;
create table city_closedway (
	relation_id INTEGER ,
	way_id INTEGER,
	tags hstore ,
	geog geography("POLYGON", 4326)	
	); 
CREATE INDEX idx_city_closedway_relation_id ON city_closedway(relation_id);

-- Fill of table city_way is done by s5---.php

-- Create list of nodes for each city
-- Here we recompute the geometry of each node as a geography for computations
DROP TABLE IF EXISTS city_node;
create table city_node (
	relation_id INTEGER ,
	node_id INTEGER,
	tags hstore ,
	geog geography("POINT", 4326)	
	);  
CREATE INDEX idx_city_node_relation_id ON city_node(relation_id);

-- Fill of table city_node is done by s5---.php

DROP TABLE IF EXISTS city_w_data;
CREATE TABLE city_w_data (
  type VARCHAR(64), -- highway:primary, railway:rail, waterway:stream, power:line, ...
	relation_id INTEGER,
	count INTEGER,
  length FLOAT
);
CREATE INDEX ON city_w_data(type, relation_id);

DROP TABLE IF EXISTS city_land_data;
CREATE TABLE city_land_data (
  type VARCHAR(64),
	relation_id INTEGER,
	area FLOAT
);
CREATE INDEX ON city_land_data(type, relation_id);

DROP TABLE IF EXISTS city_data;
CREATE TABLE city_data  (
  relation_id INTEGER,
  area FLOAT,
  highway_length FLOAT,
  highway_count FLOAT,
  residential_highway_count FLOAT,
  residential_highway_length FLOAT,

  building_count FLOAT,
  building_area FLOAT,

  residential_count FLOAT,
  residential_area FLOAT,

  places INTEGER,
  townhalls INTEGER,
  schools INTEGER,
  pows INTEGER,

	shops INTEGER,
	offices INTEGER,
	amenities INTEGER,
	leisures INTEGER,
  crafts INTEGER,
	emergencies INTEGER,
	tourisms INTEGER,
	historics INTEGER,
  militaries INTEGER,

  insee VARCHAR(100),
  population INTEGER,
  maire VARCHAR(256)
);
