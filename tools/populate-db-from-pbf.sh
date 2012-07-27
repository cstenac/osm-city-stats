#! /bin/sh

# Uses a .pbf file to populate the postgresql database

MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
. $MYDIR/../config/config.sh.inc

# Set a valid temporary dir
JAVACMD_OPTIONS=-Djava.io.tmpdir=$JAVA_TMPDIR
mkdir -p $JAVA_TMPDIR
export JAVACMD_OPTIONS

INFILE=$1

cd $OSMOSIS_DIR
set -x
$OSMOSIS_BIN $* --write-pgsql host=$DB_HOST database=$DB_DB user=$DB_USER password=osm
