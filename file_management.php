<?php
//include ('db.php');
include ('bulkdataentry.php');
?>

<?php


	if (isset($_POST["path"]))
	{
		$path = $_POST["path"];
		upload_file($path);
	}

	if (isset($_GET["html_path"]))
	{
		$html_path = $_GET["html_path"];

		echo "html_path:" .$html_path;
		upload_foreign_file($html_path);
	}

	if (isset($_POST["addGUID"]) &&
		isset($_POST["aKeyword"]) ) {

		$addGUID = $_POST["addGUID"];
		$aKeyword = $_POST["aKeyword"];

		echo "addGUID:" .$addGUID. "\naKeyword:" .$aKeyword;
		$conn->tag_file($addGUID, $aKeyword);
	}

	if (isset($_POST["dekGUID"]) &&
		isset($_POST["dKeyword"])) {

		$dekGUID = $_POST["dekGUID"];
		$dKeyword = $_POST["dKeyword"];

		echo "deGUID:" .$dekGUID. "\ndKeyword:" .$dKeyword;
		$conn->untag_file($dekGUID, $dKeyword);
	}

if (isset($conn)){
	$conn->mysqli->close();
	unset($conn);
}

?>

