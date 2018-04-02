<?php

require('db.php');

$GUID = $_GET['GUID'];
$Depth = abs($_GET['Depth']);

$GoDown = ($_GET['Down'] == 'true');

if ($GoDown) {
    $res = $conn->reach_query_down($GUID, $Depth);
} else {
    $res = $conn->reach_query_up($GUID, $Depth);
}

$count = count($res);

echo "There are {$count} children";

echo "<table class=\"table\">";

$i = 0;

foreach ($res as $guid) {

    echo "<tr>";
    $sql = "SELECT * FROM File WHERE GUID = '{$guid}'";
    $res = $conn->mysqli->query($sql);
    foreach ($res->fetch_assoc() as $value) {
        echo "<td>{$value}</td>";
    }

    echo "</tr>";
}

echo "</table>";

// if ($Depth < 0) {
//     $res = $conn->reach_query_down($GUID, $Depth);
// } else {
//     $res = $conn->reach_query_up($GUID, $Depth);
// }

// var_dump($res);

?>