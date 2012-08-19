begin;
truncate table relation_members;
truncate table relations;
truncate table ways;
truncate table way_nodes;
truncate table nodes;
truncate table users;

truncate table city_way;
truncate table city_closedway;
truncate table city_node;
commit;
UPDATE city_geom SET needs_compute=0;
vacuum;
