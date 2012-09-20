DATADIR=/data/stenac/osm/data
MYDIR=`dirname $0`
MYDIR=`cd $MYDIR && pwd -P`
download_and_compute() {
   REGION=$1
   DATE=`date +%Y%m%d`
   echo "-----------------------------------------------------------------------------------"
   echo "Working for $REGION"
   echo "-----------------------------------------------------------------------------------"
   cd $DATADIR
   if ! test -f $REGION.$DATE.osm.pbf
   then
     rm -f $REGION.osm.pbf
     wget http://download.geofabrik.de/osm/europe/france/$REGION.osm.pbf
     mv $REGION.osm.pbf $REGION.$DATE.osm.pbf
   fi

   $MYDIR/compute-region-cleandb.sh  $DATADIR/$REGION.$DATE.osm.pbf
}

download_and_compute "alsace"
download_and_compute "aquitaine"
download_and_compute "auvergne"
download_and_compute "basse-normandie"
download_and_compute "bourgogne"
download_and_compute "bretagne"
download_and_compute "centre"
download_and_compute "champagne-ardenne"
download_and_compute "corse"
download_and_compute "franche-comte"
download_and_compute "haute-normandie"
download_and_compute "ile-de-france"
download_and_compute "languedoc-roussillon"
download_and_compute "limousin"
download_and_compute "lorraine"
download_and_compute "midi-pyrenees"
download_and_compute "nord-pas-de-calais"
download_and_compute "pays-de-la-loire"
download_and_compute "picardie"
download_and_compute "poitou-charentes"
download_and_compute "provence-alpes-cote-d-azur"
download_and_compute "rhone-alpes"

