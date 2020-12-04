<?php
// this value is checked in order to make sure we have access to this file
define("SkyMakeOnDBConfigConnect", "DBCONFCONNOK");
$dbconfig = array();
$dbcurrent = array();
//begin editing for your own database

/*
//Uncomment this if you want to use the install with a single db.
define("ALLOW_MULTIDB",false);
$dbconfig["default"]["dbhost"] = "dbserver";
$dbconfig["default"]["dbname"] = "skymake_db";
$dbconfig["default"]["dbuser"] = "root";
$dbconfig["default"]["dbpassword"] = "dbpassword";
*/
/*
    //Uncomment this if you want  to use multidb
    //Note: Comment the default part before doing this
    //Note: Replace parts between ### with your own data.
    define("ALLOW_MULTIDB",true);
    //Replace the following part with this when you enable multidb
    $dbconfig["###yourmaindomainwithouttldordot###"]["dbhost"] = "###dbserver###";
    $dbconfig["###yourmaindomainwithouttldordot###"]["dbname"] = "###skymake_db###";
    $dbconfig["###yourmaindomainwithouttldordot###"]["dbuser"] = "###root###";
    $dbconfig["###yourmaindomainwithouttldordot###"]["dbpassword"] = "###dbpassword###";
    Copy this template for every multidb subdomin
    $dbconfig["###yoursubdomain###"]["dbhost"] = "###dbserver###";
    $dbconfig["###yoursubdomain###"]["dbname"] = "###skymake_db###";
    $dbconfig["###yoursubdomain###"]["dbuser"] = "###root###";
    $dbconfig["###yoursubdomain###"]["dbpassword"] = "##dbpassword###";
*/
//stop editing

if(ALLOW_MULTIDB){
    $url = $_SERVER["HTTP_HOST"];
    $parsedUrl = parse_url($url);
    $host = explode('.', $parsedUrl['host']);
    $subdomain = $host[0];
    if(isset($dbconfig[$subdomain]["dbname"])){
        $dbcurrent["host"] = $dbconfig[$subdomain]["dbhost"];
        $dbcurrent["user"] = $dbconfig[$subdomain]["dbuser"];
        $dbcurrent["password"] = $dbconfig[$subdomain]["dbpassword"];
        $dbcurrent["name"] = $dbconfig[$subdomain]["dbname"];
    } else {
      include_once(__DIR__."/../SkyMakeFunctionSet/Mission-Critical-Functions/SMC.php");
      SMC::displayCrash("There is a problem with this SkyMake Setup. Please check your configuration. ERROR_CODE: MULTIDB_UNKNOWN_DOMAIN","Failed to Configure this Namespace","There was a issue in the SkyMake Database Configuration file.");
      die();
    }
} else {
    $dbcurrent["host"] = $dbconfig["default"]["dbhost"];
    $dbcurrent["user"] = $dbconfig["default"]["dbuser"];
    $dbcurrent["password"] = $dbconfig["default"]["dbpassword"];
    $dbcurrent["name"] = $dbconfig["default"]["dbname"];
}

/* Attempt to connect to MySQL database */
$link = mysqli_connect($dbcurrent["host"], $dbcurrent["user"], $dbcurrent["password"], $dbcurrent["name"]);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
$linktwo = mysqli_connect($dbcurrent["host"], $dbcurrent["user"], $dbcurrent["password"], $dbcurrent["name"]);

// Check connection
if($linktwo === false){
    die("ERROR: Could not create seconday dblink. " . mysqli_connect_error());
}
?>
