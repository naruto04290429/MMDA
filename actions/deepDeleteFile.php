<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');

var_dump($_GET);

$guid = $_GET['GUID'];
$sql = "SELECT Child FROM Daughter WHERE Parent = {$guid}";
$res = $conn->mysqli->query($sql);

while ($row = $res->fetch_assoc()){
	var_dump($row['Child']);
	$conn->delete_file($row['Child']);
}

$conn->delete_file($guid);

?>

</body>
</html>