<?php
require "vendor/autoload.php";
use PHPHtmlParser\Dom;
require('./actions/db.php');

$conn = new db_connection();
$htmlfiles = [];

function upload_foreign_file_helper($url, $acc, $depth) {

    if ($depth == 2) {
        return false;
    }

    global $conn;

    echo ($url), PHP_EOL;

    $pathinfo = pathinfo($url);

    $file_name = $pathinfo['filename'];
    $extension = $pathinfo['extension'];

    $guid = $conn->insert_file($url,  $file_name, $extension, 1, "FOREIGN_FILE", 0);

    $html = file_get_contents($url);

    //Create a new DOM document
    $dom = new DOMDocument;
    
    //Parse the HTML. The @ is used to suppress any parsing errors
    //that will be thrown if the $html string isn't valid XHTML.
    @$dom->loadHTML($html);
    
    //Get all links. You could also use any other tag name here,
    //like 'img' or 'table', to extract other tags.
    $scripts = $dom->getElementsByTagName('script');
        
    foreach($scripts as $script)
    {
        $external_url = $script->getAttribute('src');

        if (trim($external_url) == "") {
            continue;
        }

        echo $external_url, PHP_EOL;
        
        $parsed = parse_url($external_url);
        $external_url = $parsed["host"] + $parsed["path"];
    
        if (is_url($external_url) && !in_array($external_url, $acc)) {
            array_push($acc, $external_url);
            $child_guid = upload_foreign_file_helper($external_url, $acc, $depth + 1);
            if ($child_guid) {
                $conn->set_as_daughter($child_guid, $guid);                
            }
        }
    }

    $scripts = $dom->getElementsByTagName('link');
    
foreach($scripts as $script)
{
    $external_url = $script->getAttribute('href');
            if (is_url($external_url) && !in_array($external_url, $acc)) {
        array_push($acc, $external_url);
        $child_guid = upload_foreign_file_helper($external_url, $acc, $depth + 1);
        if ($child_guid) {
            $conn->set_as_daughter($child_guid, $guid);                
        }
    }
}


    $scripts = $dom->getElementsByTagName('style');
    
    foreach($scripts as $script)
    {
        $external_url = $script->getAttribute('href');

        if (trim($external_url) == "") {
            continue;
        }

        $parsed = parse_url($external_url);
        $external_url = $parsed["host"] + $parsed["path"];

        echo $external_url, PHP_EOL;
        if (is_url($external_url) && !in_array($external_url, $acc)) {
            array_push($acc, $external_url);
            $child_guid = upload_foreign_file_helper($external_url, $acc, $depth + 1);
            if ($child_guid) {
                $conn->set_as_daughter($child_guid, $guid);                
            }
        }

    }

    return $guid;
}

function upload_foreign_file($url) {
    return upload_foreign_file_helper($url, [], 0);
}

function upload_foreign_file_test() {
    return upload_foreign_file("https://www.reddit.com/");
}

function is_url($url) {
   return (filter_var($url, FILTER_VALIDATE_URL));
}

function upload_file($path) {

    global $conn, $htmlfiles;
    
    $path_parts = pathinfo($path);

    $abs_path = realpath($path);
    $file_name = $path_parts['filename'];
    if (array_key_exists('extension', $path_parts)) {
        $extension = $path_parts['extension'];
    } else{
        $extension = 'UNDEFINED';
    }
    $size = filesize($abs_path);
    $creation_date = fileatime($abs_path);
    $author = fileowner($abs_path);

    //for identifying the duplicate cnotent
    //if the file is duplicate, not insert it.
    $sql = "SELECT COUNT(*) FROM File WHERE Extension = \"{$extension}\" AND Creation_Date = {$creation_date} AND Author = {$author} AND Size = {$size}";
    $res = $conn->mysqli->query($sql);
    if (!$res) throw new $conn->mysqli->error;
    $count = $res->fetch_assoc()['COUNT(*)'];
    if ($count > 0) {
        echo "DUPLICATE FILE", PHP_EOL;
        return;
    }

    ///
    $conn->insert_file($abs_path, $file_name, $extension, $creation_date, $author, $size);

    if ($extension == 'html') {
        array_push($htmlfiles, $abs_path);
    }
    echo "File Added: ", $abs_path, PHP_EOL;

}

function upload_dir($path) {

    global $htmlfiles;

    $root = $path;
    
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
    );
    
    $paths = array($root);
    foreach ($iter as $path => $dir) {
        if ($dir->isFile()) {
            upload_file($path);
        }
    }

    foreach ($htmlfiles as $parent_path) {

        global $conn;

        $html = file_get_contents($parent_path);
        
        //Create a new DOM document
        $dom = new DOMDocument;
        
        //Parse the HTML. The @ is used to suppress any parsing errors
        //that will be thrown if the $html string isn't valid XHTML.
        @$dom->loadHTML($html);
        
        //Get all links. You could also use any other tag name here,
        //like 'img' or 'table', to extract other tags.
        $scripts = $dom->getElementsByTagName('script');
         
        $parent_guid = $conn->get_file_guid(realpath($parent_path));

        foreach($scripts as $script)
        {
            $daughter_path = $script->getAttribute('src');

            if (is_url($daughter_path)) {
                $child_guid = upload_foreign_file($daughter_path);

                $conn->unset_as_daughter($child_guid, $parent_guid);
                $conn->set_as_daughter($child_guid, $parent_guid);
            } else {

                // For some reason this is a pretty common case, so we skip it becuase it generates to many warnings
                if (trim($daughter_path) == "") {
                    continue;
                }

                $daughter_path = '/' . join('/', array(trim(dirname($parent_path), '/'), trim($daughter_path, '/')));

                $child_guid = $conn->get_file_guid(realpath($daughter_path));

                if ($child_guid == 0) {
                    trigger_error("Invalid path in HTML file ({$daughter_path}) skipping.", E_USER_WARNING);
                    continue;
                }

                echo "Daughter Added: ", $daughter_path, ", Parent: ", $parent_path , PHP_EOL;

                $parent_guid = $conn->get_file_guid(realpath($parent_path));

                $conn->unset_as_daughter($child_guid, $parent_guid);
                $conn->set_as_daughter($child_guid, $parent_guid);
            }
        
        }

        $scripts = $dom->getElementsByTagName('link');
        
       foreach($scripts as $script)
       {
           $daughter_path = $script->getAttribute('href');

           if (is_url($daughter_path)) {
               $child_guid = upload_foreign_file($daughter_path);

               $conn->unset_as_daughter($child_guid, $parent_guid);
               $conn->set_as_daughter($child_guid, $parent_guid);
           } else {

               // For some reason this is a pretty common case, so we skip it becuase it generates to many warnings
               if (trim($daughter_path) == "") {
                   continue;
               }

               $daughter_path = '/' . join('/', array(trim(dirname($parent_path), '/'), trim($daughter_path, '/')));

               $child_guid = $conn->get_file_guid(realpath($daughter_path));

               if ($child_guid == 0) {
                   trigger_error("Invalid path in HTML file ({$daughter_path}) skipping.", E_USER_WARNING);
                   continue;
               }

               echo "Daughter Added: ", $daughter_path, ", Parent: ", $parent_path , PHP_EOL;

               $parent_guid = $conn->get_file_guid(realpath($parent_path));

               $conn->unset_as_daughter($child_guid, $parent_guid);
               $conn->set_as_daughter($child_guid, $parent_guid);
           }
       
       }


    }


}

function upload_test_dir() {
    upload_dir('./test-dir');
}

?>
