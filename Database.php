<?php

require_once(__DIR__."/globals.php");
require_once(__DIR__."/config.php");
require_once(__DIR__."/Crypt.php");

/**
 *
 * File: Database.php
 * Auth: Lukas Yelle (lxy5611@g.rit.edu)
 * Date: 8/8/17
 * Desc: Contains the classes needed to securely and easily interact with a MySQL database.
 *
 * Usage:
 *          Step 1:  Configure variables in config.php to match your setup.
 *          Step 2:  Instantiate the Table class, providing the table you would like to access as the first parameter,
 *                   and an (optional) Array of the columns you would like to be encrypted as the second parameter.
 *          Step 3:  Call one of the public functions on the instance to do the operation you desire.
 *          Step 3a: All arrays sent to the public functions should be Associative, where the key is the column name
 *                   and the value is the value you are attempting to access.
 *
 **/

# ------------------------------------====== Do not modify below this line ======------------------------------------

class DB_Backend{

    /**
     *
     * This class should only be used by the Database Class, and not modified to be used outside of it. This houses
     * the core functionality layer of the Database class, and as such is not meant to be used without it.
     *
     * Attempting to instantiate this class will result in an error, and there are no public functions available in it.
     *
     **/

    private $con;
    private $connection;

    protected function  __construct(){

        GLOBAL $con;
        GLOBAL $connection;

        $this->con = $con;
        $this->connection = $connection;

    }

    protected function table_map($table){
        /**
         * Helper function for the database functions, given a table, return an array of its columns.
         * Params: $table string, table name
         * Retuens: Array, the columns present in the table given.
         **/

        $connection = $this->connection;

        $table_map = Array();

        $map = $connection -> prepare( "SHOW COLUMNS FROM `$table`");
        if($map -> execute()){
            $map -> store_result();
            $map -> bind_result($field, $type, $null, $key, $default, $extra);
            while($map -> fetch()){

                if(strpos($type, 'int') !== false){

                    $broadType = "i";

                }
                elseif($type == "double" || $type == "float" || $type == "decimal"){

                    $broadType = "d";

                }
                elseif (strpos($type, 'blob') !== false){

                    $broadType = "b";

                }
                else{

                    $broadType = "s";

                }
                $table_map[$field] = $broadType;

            }
            $map -> close();
            return $table_map;
        }
        return "Table '$table' not found.";


    }

    private function makeValuesReferenced($arr){
        /**
         * Given an array, make each of it's values referenced.
         * Returns: A referenced array
         **/
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }

    private function array_to_formatted_string($array, $surround, $separator, $associative_separator = "=", $default_associative_value = null){
        /**
         * Given an array, surround each value with the $surround character and return string separated by the given $separator.
         * Returns: A formatted string
         **/

        $string = "";
        foreach($array as $key => $value){

            if(!isAssociative($array)){

                $string.=$surround.$value.$surround.$separator;

            }
            else{

                $string.=$surround.$key.$surround.$associative_separator;
                if($default_associative_value != null){

                    $string.=clean_var($default_associative_value);

                }
                else{

                    $string.=$surround.$value.$surround;

                }
                $string.=$separator;

            }

        }
        return rtrim($string,$separator);

    }

    private function generate_select_update_query($type, $table, $array){
        /**
         * Select and Update query strings are fairly similar, so this was written to reduce redundant code.
         * Returns: The appropriate query string for the method supplied.
         * Note: For update operations, the WHERE clause MUST be added to the end of the query string.
         **/

        switch($type){
            case "select":
                return "SELECT * FROM `$table` WHERE ".$this->array_to_formatted_string($array,"`"," AND ", "=","?");
            case "update":
                return "UPDATE `$table` SET ".$this->array_to_formatted_string($array,"`",", ", "=","?");

            default:
                return "Improper type for query generation. Must be either select or update.";
        }

    }

    private function generate_select_update_prepared($table_map,$array){
        /**
         * Select and Update prepared variables are fairly similar, so this was written to reduce redundant code.
         * Returns: An array containing the variables being modified in the correct order for the prepared statement.
         **/

        $input_types = "";
        $prepared_values = Array();

        foreach ($array as $key => $value) {

            $input_types .= $table_map[$key];

        }

        array_push($prepared_values, $input_types);
        foreach ($array as $key => $value) {

            array_push($prepared_values, $value);

        }

        return $prepared_values;

    }

    private function run_query($query, $array){
        /**
         * Given a query string and an array of prepared values, run the query with those values!
         * Returns: MySQLi_STMT instance or error message.sud
         **/

        $connection = $this->connection;

        $operation = $connection -> prepare($query);
        if($operation){

            call_user_func_array(array($operation, 'bind_param'), $this->makeValuesReferenced($array));

            if($operation -> execute()){

                return $operation;

            }
            $operation -> close();
            return "Failed to run query: $query,".json_encode($array);

        }
        return "Failed to prepare Query string: $query. \n Error detail: ".mysqli_error($connection);
    }

    protected function select_array($table, $array, $table_map = false){
        /**
         * Global function to make selecting from the database easy and secure.
         * Params: $table string - the name of the table to select from, $array Associative Array - the where clause.
         * Returns: A failure message or MySQLi object.
         **/

        if(!$table_map)$table_map = $this->table_map($table);
        if($table_map){

            $query = $this->generate_select_update_query("select", $table, $array);

            $prepared_values = $this->generate_select_update_prepared($table_map, $array);

            return $this->run_query($query, $prepared_values);

        }
        return "Table not found.";

    }

    protected function update_array($table, $array, $where, $table_map = false){
        /**
         * Global function to make updating values in the database easy and secure.
         * Params: $table string - the name of the table to update, $array Associative Array - values to update, $where Associative Array - where clause
         * Returns: A success message or MySQLi object.
         **/

        if(!$table_map)$table_map = $this->table_map($table);
        if($table_map){

            $query = $this->generate_select_update_query("update", $table, $array)." WHERE ".$this->array_to_formatted_string($where,"`"," AND ", "=","?");

            foreach($where as $key => $value){
                $array[$key] = $value;
            }

            $prepared_values = $this->generate_select_update_prepared($table_map, $array);

            $result = $this->run_query($query,$prepared_values);

            if($result->error != ""){
                return $result->error;
            }

            return $result instanceof MySQLi || $result instanceof MySQLi_STMT ? "Success." : $result;

        }
        return "Table not found.";

    }

    protected function insert_array($table, $array, $table_map = false, $debug = false){
        /**
         * Global function to make inserting into the database easy and secure.
         * Params: $table string - the name of the table to insert into, $array Array - the values to insert.
         * Returns: A success / failure message depicting whether or not your query succeeded.
         * Notes: When inserting, it is necessary to provide an empty string ("") as the 'id' parameter for any table.
         **/

        if(!$table_map)$table_map = $this->table_map($table);
        if($table_map){

            if(isAssociative($array)){

                /*
                 * The new way of inserting arrays, associatively, involves several steps.
                 *
                 * 1: Verify that the sent array doesn't contain properties that aren't in the table map.
                 * 2a: Verify that all of the required values are sent.
                 * 2b: If there are missing values, a default one will be added based on the column type.
                 * 3: The array of sent values and any missing values will be combined to form the correct prepared statement.
                 * 4: This array replaces the sent array, and then is passed to the original algorithm.
                 *
                 **/

                $verified_array = Array();
                // Check if the sent array has any keys that are not in the table map.
                foreach($array as $column => $value){

                    if(!array_key_exists($column, $table_map)){

                        return json_encode(Array("Error"=>"Column '$column' does not exist in the table '$table'."));

                    }
                    // If the sent column is in the table map, add it to the verified array.
                    $verified_array[$column] = $value;

                }

                $array = Array();

                // Loop over the table map to find missing values in the verified array.
                foreach($table_map as $column => $type){

                    if(!array_key_exists($column, $verified_array)){

                        switch($type){
                            case "i":
                                $value = 0;
                                break;
                            case "d":
                                $value = 0.0;
                                break;
                            case "s":
                                $value = "";
                                break;
                            default:
                                $value = "";
                                break;
                        }
                        $verified_array[$column] = $value;
                        if($debug)error_log("Warning: Missing value for column '$column' when inserting into '$table'. Using default value '$value'.");

                    }

                    // Configure the array to match the correct column order of the table map.
                    array_push($array, $verified_array[$column]);

                }

            }
            else{

                error_log("Warning: Usage of insert_array without associative array passed. Recommend switching to new method.");

            }
            if(sizeof($array) == sizeof($table_map)){

                // Generate the types string and begin building the query string
                $input_types = "";
                $query = "INSERT INTO `$table`(";
                foreach($table_map as $column => $type){

                    $input_types .= $type;
                    $query .= "`$column`,";

                }

                $query = rtrim($query,',').") VALUES ("; // Remove the last comma from the query

                // Generate the referenced array for data binding, and finish building the query string
                $insert_array = array();
                array_push($insert_array,$input_types);
                foreach($array as $input_value){

                    array_push($insert_array, clean_var($input_value));
                    $query .= "?,";

                }

                $query = rtrim($query,',').")";
                $result = $this->run_query($query,$insert_array);

                return $result instanceof MySQLi || $result instanceof MySQLi_STMT ? "Success." : $result;

            }
            return "Size mismatch, make sure you are sending the required number of values. ".sizeof($array)." != ".sizeof($table_map);

        }
        return "Table not found.";

    }

    protected function select_all_backend($table, $query_addition = "")
    {
        # Selects all of the items in a given table.
        # $query_addition: adds a string to the end of the query. Makes this function useful for complex queries.
        # Returns - MySql object.

        $connection = $this->connection;

        $object = $connection->prepare("SELECT * FROM `$table`$query_addition");
        $object->execute();
        $object->store_result();

        return $object;

    }

}

class Database extends DB_Backend {

    /**
     *
     * This class is also not meant for direct usage, however it is possible to use it unlike DB_Backend. It is highly
     * unrecommended to do so, but it is possible.
     *
     * Recommended usage can be found at the top of this file.
     *
     **/

    protected $encrypted_columns = Array();
    protected $crypt;
    protected $table;
    protected $map;
    protected $backend;

    public function __construct(){

        parent::__construct();
        $this->crypt = new Crypt();

    }

    private function encrypt_array_values($array){

        foreach($array as $key => $value){

            if(in_array($key,$this->encrypted_columns) || $this->encrypted_columns === true){

                $array[$key] = $this->crypt->crypt($value);

            }

        }

        return $array;

    }

    private function decrypt_array_values($array){

        foreach($array as $key => $value){

            if(in_array($key,$this->encrypted_columns) || $this->encrypted_columns === true){

                $array[$key] = $this->crypt->decrypt($value);

            }

        }

        return $array;

    }

    private function decrypt_multidimensional_array_values($array){

        // Loop over all of the rows in the array, and decrypt the correct columns.
        for($decrypt_data_row = 0; $decrypt_data_row < sizeof($array); $decrypt_data_row++){

            $array[$decrypt_data_row] = $this->decrypt_array_values($array[$decrypt_data_row]);

        }

        return $array;

    }

    private function array_from_statement($stmt){

        if($stmt instanceof MySQLi_STMT){

            $data = $stmt->result_metadata();
            $fields = array();
            $currentrow = array();
            $results = array();

            // Store references to keys in $currentrow
            while ($field = mysqli_fetch_field($data)) {
                $fields[] = &$currentrow[$field->name];
            }

            // Bind statement to $currentrow using the array of references
            call_user_func_array(array($stmt,'bind_result'), $fields);

            // Iteratively refresh $currentrow array using "fetch", store values from each row in $results array
            $i = 0;
            while ($stmt->fetch()) {
                $results[$i] = array(); //this is supposed to be outside the foreach
                foreach($currentrow as $key => $val) {
                    $results[$i][$key] = $val;
                }
                $i++;
            }
            $stmt->close();

            return $results;

        }

        error_log("SXSDatabase Error: Function array_from_statement could not generate an associative array because it was not sent a valid MySQLi Statement.");
        return Array("Error"=>"Must send a valid MySQLi Statement.");

    }

    public function check_column_exists($column){

        return array_key_exists($column,$this->map);

    }

    public function insert($array){

        $array = $this->encrypt_array_values($array);

        return $this->insert_array($this->table, $array, $this->map);

    }

    public function select($array){

        // If you are going to be decrypting some of the data you are selecting after the query, you must first encrypt
        // the select values, as they are stored in the database encrypted.

        $array = $this->encrypt_array_values($array);

        // Run the select array query.
        $stmt = $this->select_array($this->table, $array, $this->map);

        // If it was a successful query.
        if($stmt instanceof MySQLi_STMT){

            // Generate a multi-dimensional array of results.
            $returnable = $this->array_from_statement($stmt);

            // Decrypt any encrypted columns.
            $returnable = $this->decrypt_multidimensional_array_values($returnable);

            return $returnable;

        }

        return Array("Error"=>"Select_array did not return a MySQL Statement. Result: '$stmt'");

    }

    public function select_all($query_addition = ""){

        $stmt = $this->select_all_backend($this->table, $query_addition);

        if($stmt instanceof MySQLi_STMT){

            $returnable = $this->array_from_statement($stmt);

            $returnable = $this->decrypt_multidimensional_array_values($returnable);

            return $returnable;

        }

        return Array("Error" => "Select_all did not return a MySQL Statement. Result:'$stmt'");

    }

    public function update($array,$where)
    {

        $array = $this->encrypt_array_values($array);
        $where = $this->encrypt_array_values($where);

        $stmt = $this->update_array($this->table, $array, $where, $this->map);

        if ($stmt === "Success.") {

            return "Success.";

        }

        error_log("SXSDatabase Error: Unable to update columns in table '$this->table'. Result: $stmt");
        return $stmt;

    }

    public function check_exists($array){

        return sizeof($this->select($array)) !== 0;

    }

}

class Table extends Database{

    /**
     *
     * This is the class you should instantiate to work with your database easily and reliably. It takes the work out of
     * setting up the Database class.
     *
     * Usage instructions may be found at the top of this file.
     *
     **/

    public function __construct($table,$encrypted_columns = Array()){

        parent::__construct();

        $this->encrypted_columns = $encrypted_columns;
        $this->table = $table;
        $this->map = $this->table_map($table);

        if(!$this->map){

            error_log("Error while initializing SXSTable '$table'. Table Mapping failed.");

        }

    }


}