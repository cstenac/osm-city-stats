#! /bin/sh

MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`

REGIONFILE=$1


banner (){
  echo "#############################################"
  echo "# `date +%Y/%m/%d-%H:%M:%S`: $1"
  echo "#############################################"
}

cd $MYDIR
echo "Working in $PWD"

banner "Importing data to db"
../tools/sqlexec.sh -f s0-clear-osmosis-data.sql
../tools/populate-db-from-pbf.sh --read-pbf $REGIONFILE

banner "Computing ways"
php s1-export-ways.php full

banner "Computing closed ways"
php s2-compute-closed-ways.php

banner "Computing city geoms"
php s3-compute-city-geom.php

banner "Computing city ways/closedways/nodes"
php s4a-compute-target-ways.php full
php s4b-compute-target-closedways.php full
php s4c-compute-target-nodes.php full

banner "Computing city data"
php s5-compute-target-data.php full

banner "Done"
