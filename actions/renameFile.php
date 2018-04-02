<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');



$sql = "UPDATE File SET Name = \"{$_GET['Name']}\" WHERE GUID = {$_GET['GUID']}";

$conn->mysqli->query($sql);

?>

</body>
</html>