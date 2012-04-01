#! /bin/sh

# Enables PostGIS support on a new database, and import the OSM 
# schema to it

export PATH=/usr/lib/postgresql/8.4/bin

set -x
MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
. $MYDIR/../config/config.sh.inc


psql -h $DB_HOST -p $DB_PORT -d $DB_DB -U $DB_USER -f /usr/share/postgresql/8.4/contrib/postgis-1.5/postgis.sql
psql -h $DB_HOST -p $DB_PORT -d $DB_DB -U $DB_USER -f /usr/share/postgresql/8.4/contrib/postgis-1.5/spatial_ref_sys.sql
psql -h $DB_HOST -p $DB_PORT -d $DB_DB -U $DB_USER -f /usr/share/postgresql/8.4/contrib/hstore.sql
psql -h $DB_HOST -p $DB_PORT -d $DB_DB -U $DB_USER -f $OSMOSIS_DIR/package/script/pgsnapshot_schema_0.6.sql
