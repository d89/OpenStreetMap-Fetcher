<?php
define("DEBUG", false);
define("IS_LIVE", $_SERVER['HTTP_HOST'] != "localhost");


define("DB_HOST", "secret");
define("DB", "secret");
define("DB_USER", "secret");
define("DB_PW", "secret");


require_once "Exceptions.php";
require_once "Database.php";

if (DEBUG)
    error_reporting(E_ALL);
else
    error_reporting(0);