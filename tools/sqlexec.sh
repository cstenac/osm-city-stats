#! /bin/sh

# Connects to the database (when called without arguments) or 
# Executes a .sql script (called with -f file)

MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
. $MYDIR/../config/config.sh.inc

$PG_PATH/psql -h $DB_HOST -p $DB_PORT -d $DB_DB -U $DB_USER  $*
