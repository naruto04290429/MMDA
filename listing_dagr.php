<?php
include ('db.php');
?>

<?php

$result = $conn->select_all_files();

if (!$result){
	die("Database query failed.");
}
echo '<h1 align="center">Show the DAGRS</h1>';
echo '<table align="center" cellpadding="5">';

while ($row = $result->fetch_assoc()){
		echo '
		<tr>
		<td>'.$row['Path'].'</td>
		<td>'.$row['Name'].'</td>
		<td>'.$row['Extension'].'</td>
		<td>'.$row['Creation_Date'].'</td>
		<td>'.$row['Author'].'</td>
		<td>'.$row['Size'].'</td>
		</tr>
		';
	}

echo '</table>';

mysqli_free_result ($result);
if (isset($conn)){
	mysqli_close($conn);
	unset($conn);
}

?>