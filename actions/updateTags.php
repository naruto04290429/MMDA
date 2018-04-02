<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');

$guid = $_GET['GUID'];

var_dump(explode(',',$_GET['TagString']));

$sql = "DELETE FROM Tag WHERE GUID = {$guid}";

$conn->mysqli->query($sql);

foreach (explode(',',$_GET['TagString']) as $tag) {
    $conn->tag_file($guid, $tag);
}

?>
</body>
</html>