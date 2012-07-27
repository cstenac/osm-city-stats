#! /bin/sh

# This script must be executed by a root user on the database
# with the right to create databases

set -x
MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
. $MYDIR/../config/config.sh.inc

$PG_PATH/dropdb $DB_DB
$PG_PATH/createdb $DB_DB
$PG_PATH/createlang plpgsql $DB_DB
$PG8PATH/psql -p $DB_PORT -d $DB_DB --command "ALTER DATABASE $DB_DB OWNER TO $DB_USER"
