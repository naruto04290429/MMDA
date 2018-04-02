<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');

var_dump($_GET);

$conn->delete_file($_GET['GUID']);

?>

</body>
</html>