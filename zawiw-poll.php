<?php
/*
Plugin Name: ZAWiW Poll
Plugin URI:
Description: Einfache Ja/Nein Terminfindung
Version: 1.1
Author: Simon Volpert
Author URI: http://svolpert.eu
License: MIT
*/

// Global to share messages after update;
$zawiw_poll_message = "";

// INCLUDES
require_once dirname( __FILE__ ) .'/database.php';
require_once dirname( __FILE__ ) .'/render.php';
require_once dirname( __FILE__ ) .'/process.php';


?>
