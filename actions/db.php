<?php
class db_connection {
    public $mysqli;
    function __construct() {
        $host = 'cmsc389n-group-project.cmwyuj79unri.us-east-1.rds.amazonaws.com';
        $user = 'cmsc424-mmda';
        $password = 't~{BYfV_v!k6yt{)';
        $database = 'cmsc-424';
        $this->mysqli = new mysqli($host, $user, $password, $database);    
        if ($this->mysqli->connect_errno) {
            echo "Failed to connect to MySQL: " . $this->mysqli->connect_error;
        }
    }
    
    /**
     * 
     *
     * @param [type] $path
     * @param [type] $name
     * @param [type] $extension (inclue the '.')
     * @param [type] $creation_date (in unix-time)
     * @param [type] $author
     * @param [type] $size (in bytes)
     * @return GUID of new tuple
     */
    function insert_file($path, $name, $extension, $creation_date, $author, $size) {
        //TODO - clean
        $existing_file_guid = $this->get_file_guid($path);
        $this->delete_file($existing_file_guid);
        $sql = "INSERT INTO File (GUID, Path, Name, Extension, Creation_Date, Author, Size) VALUES (UUID_SHORT(), \"{$path}\", \"{$name}\", \"{$extension}\" , FROM_UNIXTIME({$creation_date}), \"{$author}\", {$size})";
        $res = $this->mysqli->query($sql);
        if (!$res) {
            throw new $this->mysqli->error;
        }
        return $this->get_file_guid($path);
    }
    function delete_file($guid) {
        $this->decategorize_file($guid);
        $tags = $this->get_file_tags($guid);
        if ($tags) {
            foreach ($tags as $tag) {
                $this->untag_file($guid, $tag);
            }
        }
        $sql = "DELETE FROM Daughter WHERE Parent = {$guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        $sql = "DELETE FROM Daughter WHERE Child = {$guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        $sql = "DELETE FROM File WHERE GUID = {$guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        // TODO - Don't worry dude I know this is far from done.
    }
    private function file_exists($guid) {
        $sql = "SELECT COUNT(*) FROM File WHERE GUID = \"{$guid}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        $count = $res->fetch_assoc()['COUNT(*)'];
        return ($count > 0);
    }
    
    /**
     * This function handles maitence of the categorization relation. Addition and deletion in that relation 
     * is abstracted away.
     * @param [type] $guid
     * @param [type] $category_name
     * @return void
     * @throws mysqli error
     */
    function categorize_file($guid, $category_name) {
        $category_name = trim($category_name);
        if(!$this->category_exists($category_name)) $this->create_category($category_name);
        $cid = $this->get_category_cid($category_name);
        $sql = "UPDATE File SET Category_CID = {$cid} where GUID = {$guid}";
        var_dump($sql);        
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }



    /**
     * This function handles maitence of the categorization relation. Addition and deletion in that relation 
     * is abstracted away.
     * Note that this function deletes categories, and if it deltes a parent category, it will also delete ]
     * the parent-child relationships to its sub categoreis.  (the sub-categoreis will still exist, they will 
     * just have no parents)
     * @param [type] $guid
     * @return void
     * @throws mysqli error
     */
    function decategorize_file($guid) {
        $sql = "SELECT Category_CID FROM File WHERE GUID = {$guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        $cid = $res->fetch_assoc()["Category_CID"];
        /**
         * A null $cid just means that the file does not have a category.
         */
        if ($cid == null) {
            return;
        }
        $sql = "UPDATE File SET Category_CID = null where GUID = {$guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        /**
         * If a category is not referenced by any file and it has no children, 
         * then it should be removed from the categorization relation.
         */
        if ($this->get_category_count($cid) == 0) {
            /**
             * If a category has a parent, remove the parent child relationship.  If you don't this will result
             * in a foreign-key constraint error.
             */
            $this->unset_as_sub_category_cid($cid);
            $this->delete_category($cid);
        }
    }
    /**
     * Returns the number of files categorized with a certain category.
     *
     * @param [type] $cid
     * @return void
     */
    private function get_category_count($cid) {
        $sql = "SELECT COUNT(*) as COUNT FROM File WHERE Category_Cid = {$cid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_assoc()["COUNT"];
    }
    /**
     * Returns an array of categories that are decendants of a given category.
     *
     * @param [type] $cid
     * @return void
     */
    function get_category_decendants($cid) {
        $acc = [];
        $this->get_category_decendants_helper($cid, $acc);
        return $acc;
    }
    /**
     * Helper function that fills a passed array.
     */
    private function get_category_decendants_helper($cid, & $acc) {
        $sql = "SELECT Child FROM Sub_Category WHERE Parent = {$cid}";
        
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        while ($row = $res->fetch_assoc()) {
            $cid = $row["Child"];
            if (!in_array($cid, $acc)) {
                array_push($acc, $cid);
                $this->get_category_decendants_helper($cid, $acc);
            }
        }
    }
    /**
     * This function handles maitence of the categorization relation. Addition and deletion in that relation 
     * is abstracted away.
     * Note: In order to maintain a tree structure, a category can only have one parent.
     * @param [type] $sub_category_name
     * @param [type] $parent_category_name
     * @return void
     * @throws category not found error
     * @throws mysqli error
     */
    function set_as_sub_category($sub_category_name, $parent_category_name) {
        // Checks that both categories exist.
        if (!$this->category_exists($sub_category_name)) 
            throw new Error("{$sub_category_name} does not exist");
        if (!$this->category_exists($parent_category_name)) 
            throw new Error("{$parent_category_name} does not exist");
        $sub_category_cid = $this->get_category_cid($sub_category_name);
        /**
         * Categories can only have a single parent.
         */
        if ($this->get_category_parent($sub_category_cid)) {
            throw new Error("Category already has a parent");
        }
        $parent_category_cid = $this->get_category_cid($parent_category_name);
        $sql = "INSERT INTO Sub_Category (Parent, Child) VALUES ({$parent_category_cid}, {$sub_category_cid})";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }
    /**
     * @param [type] $sub_category_cid
     * @return false if there is no parent, otherwise the Parent CID
     */
    private function get_category_parent($sub_category_cid) {
        $sql = "SELECT Parent FROM Sub_Category WHERE Child = {$sub_category_cid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        if (count($res) == 0) {
            return false;
        } else {
            return $res->fetch_assoc()["Parent"];
        }
    }
    /**
     * This function handles maitence of the categorization relation. Addition and deletion in that relation 
     * is abstracted away.
     * @param [type] $sub_category_name
     * @return void
     */
    function unset_as_sub_category($sub_category_name) {
        if (!$this->category_exists($sub_category_name)) throw new Error("{$sub_category_name} does not exist");
        $sub_category_cid = $this->get_category_cid($sub_category_name);
        $this->unset_as_sub_category_cid($sub_category_cid);
    }
    private function unset_as_sub_category_cid($sub_category_cid) {
        $sql = "DELETE FROM Sub_Category WHERE CHILD = {$sub_category_cid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }
    /**
     * @param [type] $category_name
     * @return array of resulting tuples 
     * @throws category not found error
     * @throws mysqli error
     */
    function get_all_files_of_category($category_name) {   
        if (!$this->category_exists($category_name))
            return 0;    
        //throw new Error("{$category_name} does not exist!");
        $cid = $this->get_category_cid($category_name);
        $sql = "SELECT * FROM File WHERE Category_CID = {$cid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_all();
    }
    private function category_exists($category_name) {
        $sql = "SELECT COUNT(*) FROM Category WHERE Name = \"{$category_name}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        $count = $res->fetch_assoc()['COUNT(*)'];
        return ($count > 0);
    }
    /**
     * Creates category with specified name.
     *
     * @param String $category_name
     * @return CID of the newly created category, or false if the category allready exists.
     * @throws mysqli error 
     */
    function create_category(String $category_name) {
        if ($this->category_exists($category_name)) {
            return false;
        }
        $sql = "INSERT INTO Category (CID, Name) VALUES (UUID_SHORT(), \"{$category_name}\")";    
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $this->get_category_cid($category_name);
    }
    /**
     * If you delete a parent-category, the relationship to it's children are also deleted.
     */
    private function delete_category($cid) {
        $res = $this->get_category_decendants($cid);
        
        foreach ($res as $child_cid) {
            $this->unset_as_sub_category_cid($child_cid);
        }
        $sql = "DELETE FROM Category WHERE CID = {$cid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }
    /**
     * This function handles maitence of the Tag and Keyword relation. Addition and deletion in those 
     * relations is abstracted away.
     * @param [type] $guid
     * @param [type] $keyword
     * @return void
     * @throws myslqi error
     */
    function tag_file($guid, $keyword) {
        if (!$this->keyword_exists($keyword)) $this->create_keyword($keyword);
        $kid = $this->get_keyword_id($keyword);
        $sql = "INSERT INTO Tag (GUID, KID) VALUES({$guid}, {$kid})";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }
    /**
     * This function handles maitence of the Tag and Keyword relation. Addition and deletion in those 
     * relations is abstracted away.
     * @param [type] $guid
     * @param [type] $keyword
     * @return void
     * @throws mysqli error
     */
    function untag_file($guid, $keyword) {
        if (!$this->keyword_exists($keyword)) $this->create_keyword($keyword);
        
        $kid = $this->get_keyword_id($keyword);
        
        return $this->untag_file_id($guid, $kid);
    }
    private function untag_file_id($guid, $kid) {
        $sql = "DELETE FROM Tag WHERE GUID = {$guid} and KID = {$kid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        // If the file is keyword isn't used anywhere, delete it!
        $sql = "SELECT COUNT(*) AS COUNT FROM Tag WHERE KID = {$kid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        if ($res->fetch_assoc()["COUNT"] == 0) {
            $sql = "DELETE FROM Keyword WHERE KID = {$kid}";
            $res = $this->mysqli->query($sql);
            if (!$res) throw new $this->mysqli->error;
        }
    }
    function get_file_tags($guid) {
        $sql = "SELECT Name FROM Tag natural join Keyword Where GUID = {$guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_assoc();
    }
    private function keyword_exists($keyword) {    
        $sql = "SELECT COUNT(*) FROM Keyword WHERE Name = \"{$keyword}\"";
        $res = $this->mysqli->query($sql);
        
        if (!$res) throw new $this->mysqli->error;
    
        $count = $res->fetch_assoc()['COUNT(*)'];
        
        return ($count > 0);
    }
    private function create_keyword($keyword) {
        $sql = "INSERT INTO Keyword (KID, Name) VALUES (UUID_SHORT(), \"{$keyword}\")";
        $res = $this->mysqli->query($sql);
        
        if (!$res) throw new $this->mysqli->error;
    }
    /**
     * @param [type] $child_guid
     * @param [type] $parent_guid
     * @return void
     * @throws GUID not found error
     * @throws mysqli error
     */
    function set_as_daughter($child_guid, $parent_guid) {
               
        if (!$this->file_exists($child_guid)) {
            throw new Error("{$child_guid} not found!");
        }
        if (!$this->file_exists($parent_guid)) {
            throw new Error("{$parent_guid} not found!");
        }
        $sql = "INSERT INTO Daughter (Parent, Child) VALUES ({$parent_guid}, {$child_guid})";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }
    /**
     * @param [type] $child_guid
     * @param [type] $parent_guid
     * @return void
     * @throws GUID not found error
     * @throws mysqli error 
     */
    function unset_as_daughter($child_guid, $parent_guid) {
        if (!$this->file_exists($child_guid)) {
            throw new Error("{$child_guid} not found!");
        }
        if (!$this->file_exists($parent_guid)) {
            throw new Error("{$parent_guid} not found!");
        }
        $sql = "DELETE FROM Daughter WHERE Parent = {$parent_guid} and Child = {$child_guid}";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
    }
    function get_orphan_files() {
        $sql = "
        select guid from
        File
        where guid not in
              (select parent
              from Daughter)";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_all();
    }
    function get_sterile_files() {
        $sql = "
        select guid from
        File
        where guid not in
              (select parent
              from Daughter)";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_all();
    }
    /**
     * @param [int] $start (unix time)
     * @param [int] $end (unix time)
     * @return void
     */
    function time_range_dagr_report($start, $end) {
        $sql = "SELECT *
        FROM File
        WHERE Creation_Date < {$end}
        AND Creation_Date > {$start}";
        
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        
        return $res;
    }
    // Getters
    function get_keyword_id($keyword) {
        $sql = "SELECT KID FROM Keyword WHERE Name = \"{$keyword}\"";
        $res = $this->mysqli->query($sql);
        
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_assoc()['KID'];
    }
    function reach_query_down(int $guid, int $depth) {
        $acc = [];
        $this->reach_query_down_helper($guid, $depth, $acc);
        return $acc;
    }
    function reach_query_down_helper(int $guid, int $depth, & $acc) {
        if ($depth > 0) {
            $sql = 
            "SELECT Child as GUID
            FROM Daughter
            WHERE Parent = {$guid}";
            $res = $this->mysqli->query($sql);
            if (!$res) throw new $this->mysqli->error;
            
            $res->data_seek(0);
            while ($row = $res->fetch_assoc()) {
                $child_guid = (int)$row['GUID'];
                if (!in_array($child_guid, $acc)) {
                    array_push($acc, $child_guid);
                    $this->reach_query_down_helper($child_guid, $depth - 1, $acc);
                }
            }
        }
    }
    function reach_query_up(int $guid, int $depth) {
        $acc = [];
        $this->reach_query_up_helper($guid, $depth, $acc);
        return $acc;
    }
    private function reach_query_up_helper(int $guid, int $depth, & $acc) {
        if ($depth > 0) {
            $sql = 
            "SELECT Parent as GUID
            FROM Daughter
            WHERE Child = {$guid}";
            $res = $this->mysqli->query($sql);
            if (!$res) throw new $this->mysqli->error;
            
            $res->data_seek(0);
            while ($row = $res->fetch_assoc()) {
                $parent_guid = (int)$row['GUID'];
                if (!in_array($parent_guid, $acc)) {
                    array_push($acc, $parent_guid);
                    $this->reach_query_up_helper($parent_guid, $depth - 1, $acc);
                }
            }
        }
    }
    /**
     * @param [type] $path
     * @returns GUID or 0 if the file is not found
     */
    function get_file_guid($path) {
        $sql = "SELECT GUID FROM File WHERE Path = \"{$path}\"";
        $res = $this->mysqli->query($sql);
        if ($res->num_rows == 0) {
            return 0;
        } else {
            return $res->fetch_assoc()['GUID'];
        }
    }
    
    function get_category_cid($category_name) {
        $sql = "SELECT CID FROM Category WHERE Name = \"{$category_name}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_assoc()['CID'];
    }
/** TESTS BELOW */
    
    /**
     * Tests basic functionality 
     *
     * @param [type] $conn
     * @return void
     */
    private function categorize_file_test1($guid) {
        $test_category = uniqid();
        // Tagging a category
        $this->categorize_file($guid, $test_category);
        assert($this->get_all_files_of_category($test_category)[0][0] == $guid);
        // Un-Categorizing a file 
        $this->decategorize_file($guid);
        assert($this->get_all_files_of_category($test_category) == 0);
        // Testing that deleteing an un-used category deletes it from the relation
        $sql = "SELECT COUNT(*) as COUNT FROM Category WHERE Name = \"{$test_category}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        assert($res->fetch_assoc()["COUNT"] == 0);
    }
    /**
     * Asserting that the removal of a child category does not remove parent category if it is used.
     *
     * @param [type] $conn
     * @param [type] $child_guid
     * @param [type] $parent_guid
     * @return void
     */
    private function categorize_file_test2($child_guid, $parent_guid) {
        $parent_category = uniqid();
        $child_category = uniqid();
        
        $this->categorize_file($child_guid, $child_category);
        $this->categorize_file($parent_guid, $parent_category);
        $this->set_as_sub_category($child_category, $parent_category);
        $this->decategorize_file($child_guid);
        assert($this->get_all_files_of_category($parent_category)[0][0] == $parent_guid);
        $this->decategorize_file($parent_guid);
        $sql = "SELECT COUNT(*) as COUNT FROM Category WHERE Name = \"{$parent_category}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        assert($res->fetch_assoc()["COUNT"] == 0);
        
        $sql = "SELECT COUNT(*) as COUNT FROM Category WHERE Name = \"{$child_category}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        assert($res->fetch_assoc()["COUNT"] == 0);
    }
    private function file_deletion_test() {
        $guid = $this->insert_file(uniqid(), "file", ".txt", 420, "blazeit", 69);
        $test_category = uniqid();
        $test_tag = uniqid();
        $this->categorize_file($guid, $test_category);
        $this->tag_file($guid, $test_tag);
        $test_KID = $this->get_keyword_id($test_tag);
        $this->delete_file($guid);
        $sql = "SELECT COUNT(*) as COUNT FROM Category WHERE Name = \"{$test_category}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        assert($res->fetch_assoc()["COUNT"] == 0);
        $sql = "SELECT COUNT(*) as COUNT FROM Tag WHERE KID = \"{$test_KID}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        assert($res->fetch_assoc()["COUNT"] == 0);
        $sql = "SELECT COUNT(*) as COUNT FROM Keyword WHERE Name = \"{$test_tag}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        assert($res->fetch_assoc()["COUNT"] == 0);
    }
    private function reach_query_test() {
        $sql = "SELECT PARENT FROM Daughter";
        $res = $this->mysqli->query($sql);
        $res->data_seek(0);
        while ($row = $res->fetch_assoc()) {
            $parent_guid = $row['PARENT'];
            
            $arr = $this->reach_query_down($parent_guid, 4);
            
            foreach ($arr as $child_guid) {
                $parent_array = $this->reach_query_up($child_guid, 4);            
                assert(in_array($parent_guid, $parent_array));
            }
        }
        $sql = "SELECT CHILD FROM Daughter";
        $res = $this->mysqli->query($sql);
        $res->data_seek(0);
        while ($row = $res->fetch_assoc()) {
            $child_guid = $row['CHILD'];
            
            $arr = $this->reach_query_up($child_guid, 4);
            
            foreach ($arr as $parent_guid) {
                $child_array = $this->reach_query_down($parent_guid, 4);            
                assert(in_array($child_guid, $child_array));
            }
        }
    }
    function select_all_files() {
        $sql = "SELECT * FROM File 
        LEFT FULL JOIN 
        ";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res;
    }
    function select_all_categories() {
        $sql = "SELECT * FROM Category";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res;
    }
    function select_all_categories_relations() {
        $sql = "SELECT * FROM Sub_Category";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res;
    }
    function get_category_name($category_cid) {
        $sql = "SELECT Name FROM Category WHERE CID = \"{$category_cid}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_assoc()['Name'];
    }
    function get_keyword_name($guid) {
        $sql = "SELECT Name FROM Tag natural join Keyword Where GUID = \"{$guid}\"";
        $res = $this->mysqli->query($sql);
        if (!$res) throw new $this->mysqli->error;
        return $res->fetch_assoc()['Name'];
    }
    function run_tests() {
        // $guid = $this->insert_file(uniqid(), "file", ".txt", 420, "blazeit", 69);    
        // $this->categorize_file_test1($guid);
        // $this->delete_file($guid);
    
        // $child = $this->insert_file(uniqid(), "file", ".txt", 420, "blazeit", 69);    
        // $parent = $this->insert_file(uniqid(), "file", ".txt", 420, "blazeit", 69);
        // $this->categorize_file_test2($child, $parent);
        // $this->delete_file($child);
        // $this->delete_file($parent);
        // $this->file_deletion_test();
        $this->reach_query_test();
    }
    
}
$conn = new db_connection();
//$conn->run_tests();
?>