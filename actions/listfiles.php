<!DOCTYPE html>
<html>
<body>

<?php

require_once('db.php');

$file_name = trim($_GET["Name"]);
$category = trim($_GET["Category"]);

$sql = <<<EOT
select File.GUID, File.Name as Name, Category.Name as Category, Path, Extension, Creation_Date, Size, Author, GROUP_CONCAT(Keyword.Name SEPARATOR ', ') as Tags
From File
left outer join Category on CID = Category_CID
left outer join Tag on File.GUID = Tag.GUID
left outer join Keyword on Tag.KID = Keyword.KID
WHERE File.Name like '%{$file_name}%' and
Extension like '%{$_GET['Extension']}%' and 
Creation_Date >= FROM_UNIXTIME({$_GET['After']}) and  
Creation_Date <= FROM_UNIXTIME({$_GET['Before']}) and 
Size >= {$_GET['MinSize']} and
Size <= {$_GET['MaxSize']} 
EOT;


if ($category != '') {
    $sql .= " and (Category.Name like '%{$category}%'";
    
    $cid = $conn->get_category_cid($category);
    if ($cid) {
        foreach ($conn->get_category_decendants($cid) as $sub_cid) {
            $sql .= " or Category.CID = $sub_cid";
        }
    }
    $sql .= ")";
}

$tags = explode(',', trim($_GET['TagString']));


if (count($tags) > 0 && trim($tags[0]) != '') {

    $sql .= "and (Keyword.Name = \"{$tags[0]}\"";

    for ($i = 1; $i < count($tags); $i++)  {
        $sql .= "or Keyword.Name = \"{$tags[$i]}\"";
    }

    $sql .= ") ";

}

if ($_GET['Orphan'] === "true") {
    $sql .= 'and File.GUID not in (select Child from Daughter)';
}

if ($_GET['Sterile'] === "true") {
    $sql .= 'and File.GUID not in (select Parent from Daughter)';
}

$offset = ($_GET['Page'] - 1) * $_GET['PerPage'];

$sql .= " group by File.GUID";

$limit = " LIMIT {$_GET['PerPage']} OFFSET {$offset}";

$result = $conn->mysqli->query("SELECT COUNT(*) as Count FROM ({$sql}) AS X");

if (!$result) var_dump($conn->mysqli->error);

$numrows = $result->fetch_assoc()['Count'];

$result = $conn->mysqli->query("{$sql}" . $limit);

if (!$result) var_dump($conn->mysqli->error);

echo "<p><strong>SQL Statement:</strong> <code>${sql}</code></p>";

$total_pages = ceil($numrows / $_GET['PerPage']);

echo "<p style=\"text-align:center\">Displaying page {$_GET['Page']} of {$total_pages} (Total Rows: {$numrows}</em>)</p>";

echo '<table class="table table-responsive table-hover">';

echo '<thead><th scope="col">GUID</th><th scope="col">Filename</th><th scope="col">Category</th><th scope="col">Extension</th><th scope="col">Creation Date</th><th scope="col">Bytes</th><th>Author</th><th>Tags (comma seperated)</th><th></th></thead>';

$numrows = 0;

while ($row = $result->fetch_assoc()){

        echo '<tr>';

        $guid = strval($row['GUID']);

        $path = $row['Path'];

        if (substr($path, 0, 4 ) !== "http") {
            $path = "file://" . $path;
        }

        $name = $row['Name'];
        $category = $row['Category'];

        echo "<td><a href=\"{$path}\">{$row['GUID']}</a></td>";
        echo "<td><input type=\"text\" value=\"{$name}\" placeholder=\"unnamed\" onchange=\"updateName('{$guid}', this.value)\"></td>";
        echo "<td><input type=\"text\" value=\"{$category}\" placeholder=\"uncategorized\" onchange=\"updateCategory('{$guid}', this.value)\"></td>";
        echo "<td>{$row['Extension']}</td>";
        echo "<td>{$row['Creation_Date']}</td>";
        echo "<td>{$row['Size']}</td>";
        echo "<td>{$row['Author']}</td>";

        echo "<td><input class=\"form-control input-sm\" id=\"inputsm\" type=\"text\" value=\"{$row['Tags']}\" onchange=\"updateTags('{$guid}', this.value)\"></td>";

        echo "<td><button type=\"button\" class=\"btn btn-danger\"  onclick=\"deleteDAGR('{$guid}')\">⌫</button></td>";

        echo '</tr>';

        $numrows += 1;
        
}

echo '</table>';

?>
</body>
</html>