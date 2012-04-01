#! /bin/sh

# This script must be executed by a root user on the database
# with the right to create databases

set -x
MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
. $MYDIR/../config/config.sh.inc

/usr/lib/postgresql/8.4/bin/dropdb $DB_DB
/usr/lib/postgresql/8.4/bin/createdb $DB_DB
/usr/lib/postgresql/8.4/bin/createlang plpgsql $DB_DB
psql -p $DB_PORT -d $DB_DB --command "ALTER DATABASE $DB_DB OWNER TO $DB_USER"
