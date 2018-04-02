<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');

var_dump($_GET);

$conn->categorize_file($_GET["GUID"], $_GET["Category"]);

?>

</body>
</html>