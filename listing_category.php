<?php
include ('./actions/db.php');
?>

<?php
$result1 = $conn->select_all_categories();
$result2 = $conn->select_all_categories_relations();
if (!$result1){
	die("Database query failed.");
}

if (!$result2){
	die("Database query failed.");
}

echo '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js">
	</script>
	</head>
	<body>';

echo '<h1 align="center">Manage Category</h1>';

echo '
	<div>
		<p>Create New Category</p>
		<input type="text" id="category" value=""><br><br>
		<button onclick="createCategory()"> create </button>
		<br><br><br>
		
		<p id="message">Create New Sub-category</p>
		Parent Category: 
		<input type="text" id="parentCategory" value=""><br>
		Child Category: 
		<input type="text" id="childCategory" value=""><br><br>
		<button onclick="createSubCategory()"> create </button>
		<button onclick="deleteSubCategory()"> delete </button>
	</div>

';

echo '<h1 align="center">Show Category</h1>';
echo '<table align="center" cellpadding="5">';

echo '
	<tr>
	<td> CID </td>
	<td> Name </td>
	</tr>';

while ($row = $result1->fetch_assoc()){
	echo '
		<tr>
		<td>'.$row['CID'].'</td>
		<td>'.$row['Name'].'</td>
		</tr>
		';
}

echo '</table>';

echo '<h1 align="center">Show Category Relation</h1>';
echo '<table align="center" cellpadding="5">';
echo '
	<tr>
	<td> Parent Category </td>
	<td> Child Category </td>
	</tr>';
while ($row = $result2->fetch_assoc()){
	
	$temp1 = $conn->get_category_name($row['Parent']);
	$temp2 = $conn->get_category_name($row['Child']);
	echo '
		<tr>
		<td>'.$temp1.'</td>
		<td>'.$temp2.'</td>
		</tr>
		';
}

echo '</table>';
echo '<script>
	function createCategory() {
		var newCategory = document.getElementById("category").value;
		console.log(newCategory);
		$.ajax({
			  		method: "POST",
			  		url: "./category_management.php",
			  		//dataType: "json",
			  		data: {
			  			newCategory: newCategory
			  		},
			  		success: function (data) {
        				console.log(data);
    				},
    				error: function(){
    					console.log("error");
    				}
				});
	}

	function createSubCategory() {
		var parentCategory = document.getElementById("parentCategory").value;
		var childCategory = document.getElementById("childCategory").value;
		console.log(parentCategory);
		console.log(childCategory);
		if(parentCategory.length==0 || childCategory.length==0) {
			document.getElementById("message").innerHTML = 
			"Have to put both existing catgories.";
			return;
		}
		$.ajax({
			  		method: "POST",
			  		url: "./category_management.php",
			  		//dataType: "json",
			  		data: {
			  			parentCategory: parentCategory,
			  			childCategory: childCategory,
			  			create: "create"
			  		},
			  		success: function (data) {
        				console.log(data);
    				},
    				error: function(){
    					console.log("error");
    				}
				});
	}

	function deleteSubCategory() {
		var parentCategory = document.getElementById("parentCategory").value;
		var childCategory = document.getElementById("childCategory").value;
		console.log(parentCategory);
		console.log(childCategory);
		if(parentCategory.length==0 || childCategory.length==0) {
			document.getElementById("message").innerHTML = 
			"Have to put both existing catgories.";
			return;
		}
		$.ajax({
			  		method: "POST",
			  		url: "./category_management.php",
			  		//dataType: "json",
			  		data: {
			  			parentCategory: parentCategory,
			  			childCategory: childCategory,
			  			create: "delete"
			  		},
			  		success: function (data) {
        				console.log(data);
    				},
    				error: function(){
    					console.log("error");
    				}
				});
	}
	</script>
	';

echo '
	</body>
	</html>
	';

?>
