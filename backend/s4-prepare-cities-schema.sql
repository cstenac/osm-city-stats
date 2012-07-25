--- vim: ts=2

--- Step 4 of the analysis:
---   Create a schema with one table for "nodes per city", one for "ways per city", and one for
---   "computed data per city"

--- Fill of this table is done in PHP by Step 5.



-- Create list of ways for each city
-- Here, we recompute the geometry of each way as a geography for computations
DROP TABLE IF EXISTS city_way;
create table city_way (
	relation_id INTEGER ,
	way_id INTEGER,
	tags hstore ,
	geog geography("LINESTRING", 4326)	
	); 

DROP TABLE IF EXISTS city_closedway;
create table city_closedway (
	relation_id INTEGER ,
	way_id INTEGER,
	tags hstore ,
	geog geography("POLYGON", 4326)	
	); 

-- Fill of table city_way is done by fill.php

-- Create list of nodes for each city
-- Here we recompute the geometry of each node as a geography for computations
DROP TABLE IF EXISTS city_node;
create table city_node (
	relation_id INTEGER ,
	node_id INTEGER,
	tags hstore ,
	geog geography("POINT", 4326)	
	);  


-- Fill of table city_node is done by fill.php



DROP TABLE IF EXISTS city_data;
CREATE TABLE city_data  (
  relation_id INTEGER,
  area FLOAT,
  highway_length FLOAT,
  highway_count FLOAT,
  building_count FLOAT,
  building_area FLOAT,

  residential_count FLOAT,
  residential_area FLOAT,

  places INTEGER,
  townhalls INTEGER,
  schools INTEGER,
  pows INTEGER
);
