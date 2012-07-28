--- vim: ts=2

--- Step 3 of the analysis: compute the full polygon of each city, from
--- the administrative boundaries in the DB

DROP TABLE IF EXISTS city_geom;
CREATE TABLE city_geom(
  relation_id INTEGER PRIMARY KEY,
  city VARCHAR(200),
  geog geography('POLYGON', 4326)
);
SELECT AddGeometryColumn('city_geom', 'geom', 4326, 'GEOMETRY', 2);
SELECT AddGeometryColumn('city_geom', 'geom_dump', 4326, 'POLYGON', 2);

-- Compute a polygon using flat geometry
INSERT INTO city_geom(relation_id, city, geom)
	SELECT r.id, MIN(hstore(r.tags) -> 'name') , ST_Polygonize(way_geometry.geom) geom
	FROM way_geometry
		INNER JOIN ways on ways.id=way_geometry.way_id
	        INNER JOIN relation_members rn on rn.member_id = ways.id
	        INNER JOIN relations r on rn.relation_id = r.id
                AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '8' GROUP BY r.id;
UPDATE city_geom SET geom_dump = (ST_Dump(geom)).geom;

-- Recompute the polygon of each city as a geography. This 
-- will be used for precise computations like city surface
update city_geom set geog = (ST_Dump(geom)).geom;

-- Also create geometry for regions. This can help us focus more the analysis

DROP TABLE IF EXISTS region_geom;
CREATE TABLE region_geom(
  relation_id INTEGER PRIMARY KEY,
  geog geography('POLYGON', 4326)
);
SELECT AddGeometryColumn('region_geom', 'geom', 4326, 'GEOMETRY', 2);
SELECT AddGeometryColumn('region_geom', 'geom_dump', 4326, 'POLYGON', 2);
INSERT INTO region_geom(relation_id, geom)
	SELECT r.id, ST_Polygonize(way_geometry.geom) geom
	FROM way_geometry
		INNER JOIN ways on ways.id=way_geometry.way_id
	        INNER JOIN relation_members rn on rn.member_id = ways.id
	        INNER JOIN relations r on rn.relation_id = r.id
                AND rn.member_type='W' AND hstore(r.tags) -> 'admin_level' = '4' GROUP BY r.id;
UPDATE region_geom SET geom_dump = (ST_Dump(geom)).geom;
update region_geom set geog = (ST_Dump(geom)).geom;
