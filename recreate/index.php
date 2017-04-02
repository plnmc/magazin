<?php
  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', '1');
  date_default_timezone_set('Asia/Novosibirsk');

  require_once("../classes/autoload.php");

  DataProcessor::stripAll();
  $key = $_GET['key'];
  if ($key == "bananafana")
  {
    $status = SystemOperations::reCreateDemoShop();
    Display::show();
    if ($status)
    {      exit(0);
    } else
    {      exit(1);
    }
  }
?>