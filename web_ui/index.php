<html>
  <head>
    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {packages:['table']});
      google.setOnLoadCallback(drawTable);
     
      function drawTable() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Name');
        data.addColumn('number', 'Area (km²)');
        data.addColumn('number', 'Highway count');
        data.addColumn('number', 'Highway length');
        data.addColumn('number', 'Residential areas count');
        data.addColumn('number', 'Residential area (% of city)');
        data.addColumn('number', 'Building count');
        data.addColumn('number', 'Built area (% of residential area)');
        data.addColumn('number', 'Built area (% of city)');
        data.addColumn('number', '"Places"');
        data.addColumn('number', 'Townhalls');
        data.addColumn('number', 'Schools');
        data.addColumn('number', 'POWs');
        data.addColumn('string', 'Link');
        var currow = 0;

<?php
  include("config.php.inc");

  $conn = pg_connect($db_conn_string) or die('Could not connect: ' . pg_last_error());

  $query = "SELECT r.id as id, r.tags -> 'name' as name, cd.area as area, cd.highway_count as highway_count, cd.highway_length as highway_length, ".
           "cd.building_count as building_count, cd.building_area as building_area, cd.residential_count as residential_count, cd.residential_area as residential_area, ".
           "cd.places as places, cd.townhalls as townhalls, cd.schools as schools, cd.pows as pows ".
           "from city_data cd inner join relations r on r.id = cd.relation_id";
  $result = pg_query($query);

  while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $id = $line["id"];
    $name = $line["name"];
    $area = round($line["area"], 2);
    $highway_count = $line["highway_count"];
    $highway_length = round($line["highway_length"], 1);
    $building_count = $line["building_count"];
    $building_area = $line["building_area"];
    $residential_count = $line["residential_count"];
    $residential_area = $line["residential_area"];
    $res_pc = round($residential_area / $area * 100, 1);
    if ($residential_area > 0) {
        $building_res_pc = round($building_area / $residential_area * 100, 1);
    } else {
        $building_res_pc = 0;
    }
        $building_pc = round($building_area / $area * 100, 1);


    $places = $line["places"]; if (!isset($places)) $places = 0;
    $townhalls = $line["townhalls"]; if (!isset($townhalls)) $townhalls = 0;
    $schools = $line["schools"]; if (!isset($schools)) $schools = 0;
    $pows = $line["pows"]; if (!isset($pows)) $pows = 0;

    echo "data.addRows(1);";

    echo "data.setCell(currow, 0, '".str_replace("'", "\'", $name)."');";
    echo "data.setCell(currow, 1, ".round($area / 1000000, 2).");";
    echo "data.setCell(currow, 2, $highway_count);";
    echo "data.setCell(currow, 3, $highway_length);";
    echo "data.setCell(currow, 4, $residential_count);";
    echo "data.setCell(currow, 5, $res_pc);";
    echo "data.setCell(currow, 6, $building_count);";
    echo "data.setCell(currow, 7, $building_res_pc);";
    echo "data.setCell(currow, 8, $building_pc);";
    echo "data.setCell(currow, 9, $places);";
    echo "data.setCell(currow, 10, $townhalls);";
    echo "data.setCell(currow, 11, $schools);";
    echo "data.setCell(currow, 12, $pows);";
    echo "data.setCell(currow, 13, '<a href=\"http://www.openstreetmap.org/browse/relation/$id\">".str_replace("'", "\'", $name)."</a>');";
    echo "currow++;\n";
   }
?>

  
        var table = new google.visualization.Table(document.getElementById('table_div'));
        table.draw(data, {showRowNumber: true, allowHtml : true});
      }
    </script>
  </head>

  <body>
    <div id='table_div'></div>
  </body>
</html>
