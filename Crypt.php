<?php

require_once(__DIR__."/config.php");

/**
 * This is a wrapper for Paragon Initiative's Halite encryption library.
 * User: luke
 * Date: 7/16/17
 * Time: 10:29 AM
 */


class Crypt
{

    private $key;

    public function __construct(){

        GLOBAL $encryption_key_path;

        if(isset($encryption_key_path) && file_exists($encryption_key_path)){



        }

    }

    public function crypt($plain_text){

        return $plain_text;

    }

    public function decrypt($cipher){

        return $cipher;

    }

}
