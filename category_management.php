<?php
include ('./actions/db.php');
?>

<?php

if (isset($_POST["newCategory"])) {
	$newCategory = $_POST["newCategory"];
	echo "Category: " .$newCategory;
	$conn->create_category($newCategory);
}

if (isset($_POST["parentCategory"])&&
	isset($_POST["childCategory"]) &&
	isset($_POST["create"])) {
	echo "here";
	$parentCategory = $_POST["parentCategory"];
	$childCategory = $_POST["childCategory"];
	$create = $_POST["create"];
	if($create == "create"){
		echo "here";
		$conn->set_as_sub_category($childCategory, $parentCategory);
	}
	else
		$conn->unset_as_sub_category($childCategory);
	echo "parentCategory: " .$parentCategory. "\nchildCategory:" .$childCategory;
}

if (isset($conn)){
	mysqli_close($conn);
	unset($conn);
}

?>
