<?php
  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', '1');
  date_default_timezone_set('Asia/Novosibirsk');

  require_once("../classes/autoload.php");

  DataProcessor::stripAll();
  $adminNavigation = new AdminNavigation($_SERVER["PHP_SELF"]);
  $adminNavigation->display();
  Display::show();

?>