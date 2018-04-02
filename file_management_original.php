<?php
include ('db.php');
?>

<?php

	if (isset($_POST["f_path"]) &&
		isset($_POST["f_name"]) &&
		isset($_POST["f_type"]) &&
		isset($_POST["f_lastModifiedDate"]) &&
		isset($_POST["f_author"]) &&
		isset($_POST["f_size"]))
	{
		$f_path = $_POST["f_path"];
		$f_name = $_POST["f_name"];
		$f_type = $_POST["f_type"];
		$f_lastModifiedDate = $_POST["f_lastModifiedDate"];
		$f_author = $_POST["f_author"];
		$f_size = $_POST["f_size"];
		$category = $_POST["category"];
		$keyword = $_POST["keyword"];

		echo "Path: " .$f_path. "\nFile:" .$f_name. "\ntype:" .$f_type. 
		"\nDate:" .$f_lastModifiedDate. "\nAuthor:" .$f_author.
		"\nsize:" .$f_size. "\ncategory:" .$category.
		"\nkeyword:" .$keyword;
		//Insertion of DAGR
		$conn->insert_file($f_path, $f_name, $f_type, 
			$f_lastModifiedDate, $f_author, $f_size);
		//Categorize and Set up keyword for the DAGR
		$guid = $conn->get_file_guid($f_path);	
		$conn->categorize_file($guid, $category);
		if($keyword != "") $conn->tag_file($guid, $keyword);
	}

	if (isset($_POST["dekGUID"])) {
		$dekGUID = $_POST["dekGUID"];
		$keyword = $conn->get_keyword_name($dekGUID);
		echo "keyword: ".$keyword;

		$conn->untag_file($dekGUID, $keyword);
	}

if (isset($conn)){
	mysqli_close($conn);
	unset($conn);
}
?>