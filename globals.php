<?php

require_once(__DIR__."/config.php");

/**
 *
 * File: globals.php
 * Auth: Lukas Yelle (lxy5611@g.rit.edu)
 * Date: 5/6/17
 * Desc: Houses global references to variables and common functions.
 *
 **/

// Define global database variables.
$con = mysqli_connect("$db_addr","$db_user","$db_pass",$db_name)or die("cannot connect");      // old style, non OOP ySQLi
$connection = new mysqli("$db_addr","$db_user","$db_pass",$db_name)or die("cannot connect");// new OOP MySQLi class
// End global database variable declaration.

function isAssociative(array $array) {
    /**
     * Given an array, check to see if it has any non-integer keys
     * Returns: boolean.
     **/
    return count(array_filter(array_keys($array), 'is_string')) > 0;
}

function clean_var($var){
    # Cleans a variable from running potentially malicious code.
    # Returns - The variable sent, just cleaned.

    GLOBAL $con;

    return mysqli_real_escape_string($con, stripslashes($var));

}

function is_posted($variable){
    # Checks if a variable, or array of variables, are set.
    # Returns - True if all supplied variables are set, false otherwise.

    if (is_array($variable) or ($variable instanceof Traversable)){

        $exists = Array();

        foreach ($variable as $var){

            if(!isset($_POST[(string)$var]) || $_POST[(string)$var] == ""){

                return false;

            }
            else{

                $exists[$var] = clean_var($_POST[$var]);

            }

        }

        return $exists;

    }

    elseif (isset($_POST[$variable])){

        return $_POST[$variable];

    }

    return false;

}