create table if not exists named_ways3 (
  id BIGINT PRIMARY KEY,
  name TEXT,
  ref TEXT,
  waytype VARCHAR(64),
  relation_ids bigint[],
  admin_levels integer[],
  centroid VARCHAR(128)
);

set search_path to zorglub,osmosis,public;
set enable_bitmapscan = false;

begin;
--delete from named_ways;
insert into named_ways3
  select id, ways.tags->'name', ways.tags->'ref',
	(CASE 
		WHEN ways.tags->'highway' = 'residential' then 'residential'
		WHEN ways.tags ? 'highway' then 'highway'
		WHEN ways.tags ? 'waterway' then 'waterway'
		WHEN ways.tags ? 'building' then 'building'
		WHEN ways.tags ? 'railway' then 'railway'
		WHEN ways.tags ? 'aerialway' then 'aerialway'
		WHEN ways.tags ? 'aeroway' then 'aeroway'
		WHEN ways.tags->'public_transform' = 'platform' then 'public_transport'
		END
	) as waytype,
	array_agg(admin_geom.relation_id) as relation_ids,
	array_agg(admin_geom.tags->'admin_level')::int[] as admin_levels,
	st_astext(st_centroid(linestring)) 
	from ways
	inner join admin_geom on  st_intersects(admin_geom.geom_dump, ways.linestring) 
	 where 
	 	(ways.tags ? 'name' OR ways.tags ? 'ref') and 
		ST_NumPoints(ways.linestring) >= 2 and 
		ST_Within(ways.linestring,
		   ST_GeomFromText('POLYGON((-7.3718 35.6662, -4.7131 36.0935, 7.7344 38.1346, 12.0850 37.0902, 15.6445 34.2345, 30.9375 33.9069, 31.2891 46.1950, 42.1875 47.5172, 41.3086 50.0642, 29.8828 56.9450, 28.8281 59.8889, 33.0469 63.7825, 29.7070 71.0741, -27.2461 66.9988, -11.6016 35.1738, -9.1406 36.0313, -7.3718 35.6662))', 4326))

	group by ways.id
;


--		 ST_GeomFromText('POLYGON((-6 41, -6 51, 10 51, 10 41, -6 41))', 4326)); 

commit;
