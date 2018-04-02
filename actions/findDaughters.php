<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');

$guid = trim($_GET["GUID"]);

$sql = <<<EOT
select GUID, File.Name as Name, Category.Name as Category, Path, Extension, Creation_Date, Size
From File
left outer join Category on CID = Category_CID

EOT;

$i = 0;
foreach ($conn->reach_query_down($guid, 1000) as $child) {
    
    if ($i == 0) {
        $sql .= " WHERE GUID = {$child}";
    } else {
        $sql .= " OR GUID = {$child}";
    }
    $i++;
}

if ($i == 0) {
    $sql .= "WHERE FALSE";
}

$result = $conn->mysqli->query($sql);

if (!$result) var_dump($conn->mysqli->error);

echo "<p><strong>SQL Statement:</strong> <code>${sql}</code></p>";

echo "<p style=\"text-align:center\"><em>A deep delete will delete the following {$result->num_rows} files.</em></p>";

echo '<table class="table table-sm">';

echo '<thead><th scope="col">GUID</th><th scope="col">Filename</th><th scope="col">Category</th></thead>';


while ($row = $result->fetch_assoc()){

        echo '<tr>';

        $guid = strval($row['GUID']);

        $path = $row['Path'];
        $name = $row['Name'];
        $category = $row['Category'];

        echo "<td>{$row['GUID']}</td>";
        echo "<td>{$row['Name']}</td>";
        echo "<td>{$row['Category']}</td>";
        

        // echo "<td><a href=\"file:///{$path}\">{$row['GUID']}</a></td>";
        // echo "<td><input type=\"text\" value=\"{$name}\" placeholder=\"unnamed\" onchange=\"updateName('{$guid}', this.value)\"></td>";
        // echo "<td><input type=\"text\" value=\"{$category}\" placeholder=\"uncategorized\" onchange=\"updateCategory('{$guid}', this.value)\"></td>";
        // echo "<td>{$row['Extension']}</td>";
        // echo "<td>{$row['Creation_Date']}</td>";
        // echo "<td>{$row['Size']}</td>";

        echo '</tr>';

        
	}

echo '</table>';

?>
</body>
</html>