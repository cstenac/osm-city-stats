#! /bin/sh

MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
. $MYDIR/../config/config.sh.inc

echo "************** STEP 1: Preparing analysis"
../tools/sqlexec.sh -f "s1-prepare-analysis.sql"
echo "************** STEP 2: Computing lines from OSM ways"
../tools/sqlexec.sh -f "s2-compute-way-lines.sql"
echo "************** STEP 3: Computing polygon for each city"
../tools/sqlexec.sh -f "s3-compute-city-geom.sql"
echo "************** STEP 4: Preparing per-city data"
../tools/sqlexec.sh -f "s4-prepare-cities-schema.sql"
echo "************** STEP 5: Computing content of cities"
php s5-compute-city-data.php
