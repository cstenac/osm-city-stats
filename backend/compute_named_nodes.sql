create table if not exists named_nodes (
  id BIGINT PRIMARY KEY,
  name TEXT,
  ref TEXT,
  nodetype VARCHAR(64),
  relation_ids bigint[],
  point VARCHAR(128)
);

set search_path to zorglub,osmosis,public;
set enable_bitmapscan = false;

begin;
delete from named_nodes;
insert into named_nodes
  select id, tags->'name', tags->'ref',
	(CASE 
		WHEN tags ? 'aeroway' then 'aeroway'
		WHEN tags->'highway' = 'bus_stop' then 'public_transport'
		WHEN tags ? 'public_transport' then 'public_transport'
		WHEN tags->'amenity' = 'shelter' then 'shelter'
		WHEN tags->'amenity' = 'bar' then 'restaurant'
		WHEN tags->'amenity' = 'cafe' then 'restaurant'
		WHEN tags->'amenity' = 'fast_food' then 'restaurant'
		WHEN tags->'amenity' = 'food_court' then 'restaurant'
		WHEN tags->'amenity' = 'restaurant' then 'restaurant'
		WHEN tags->'amenity' = 'school' then 'school'
		WHEN tags->'amenity' = 'college' then 'school'
		WHEN tags->'amenity' = 'university' then 'school'
		WHEN tags->'amenity' = 'kindergarten' then 'school'
		WHEN tags->'amenity' = 'place_of_worship' then 'place_of_worship'
		WHEN tags ? 'craft' then 'craft'
		WHEN tags ? 'historic' then 'historic'
		WHEN tags ? 'leisure' then 'leisure'
		WHEN tags ? 'office' then 'office'
		WHEN tags ? 'place' then 'place'
		WHEN tags ? 'shop' then 'shop'
		WHEN tags ? 'sport' then 'sport'
		WHEN tags ? 'tourism' then 'tourism'
		END
	) as nodetype,
	array(select relation_id from admin_geom where st_intersects(geom_dump, nodes.geom) AND tags->'admin_level' = '8'),
	st_astext(geom)
	from nodes where 
	 	(tags ? 'name' OR tags ? 'ref') and 
		ST_Within(geom,
	             ST_GeomFromText('POLYGON((-7.3718 35.6662, -4.7131 36.0935, 7.7344 38.1346, 12.0850 37.0902, 15.6445 34.2345, 30.9375 33.9069, 31.2891 46.1950, 42.1875 47.5172, 41.3086 50.0642, 29.8828 56.9450, 28.8281 59.8889, 33.0469 63.7825, 29.7070 71.0741, -27.2461 66.9988, -11.6016 35.1738, -9.1406 36.0313, -7.3718 35.6662))', 4326));
			

commit;
