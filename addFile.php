<?php
echo '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js">
	</script>
	</head>
	<body>
		<h1 align="center"> Local File Management </h1>
		<br>
		<br>
		<div>
			<h2> Uploading File </h2>
			<p>Path</p>
			<input type="text" id="path" value=""><br><br>
			<button onclick="upload_file()"> upload </button>
		</div>
		
		<br>
	';

echo '<style type="text/css">
		  .drop_zone {
		  padding: 10px;
		  border: 1px solid #ccc;
		}
		#drop_zone {
		  border: 2px dashed #bbb;
		  -moz-border-radius: 5px;
		  -webkit-border-radius: 5px;
		  border-radius: 5px;
		  padding: 25px;
		  text-align: center;
		  font: 20pt bold "Vollkorn";
		  color: #bbb;
		}
	</style>
	';

echo '<script>
		var path;
		var category;
		var keyword;
		function upload_file() {
			path = document.getElementById("path").value;
			console.log(path);

			$.ajax({
			  		method: "POST",
			  		url: "./file_management.php",
			  		//dataType: "json",
			  		data: {
			  			path: path
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



