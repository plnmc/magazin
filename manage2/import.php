<?php
  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', '1');
  date_default_timezone_set('Asia/Novosibirsk');

  require_once("../classes/autoload.php");

  DataProcessor::stripAll();

  $dbId = $_REQUEST['db_id'];
  $step = $_REQUEST['step'];
  $csv = $_REQUEST['csv'];
  $dbConnection = new DbSafeConnection();
  $creds = $dbConnection->getDbCredentialsById($dbId);
  Display::append($creds['db_name']." ".$creds['db_user']." ".$creds['db_pass']);

  if (!isset($step)) $step = 1;

  switch ($step)
  {    case 1:
      Display::append("<form action='".$_SERVER["PHP_SELF"]."' method=post>");
      Display::append("<textarea name=csv cols=100 rows=20></textarea>");
      Display::append("<input type=hidden name=step value=2>");
      Display::append("<input type=hidden name=db_id value=$dbId>");
      Display::append("<br><input type=submit value='Импорт!'></form>");
      break;
    case 2:
      $parsedCSV = ImportOperations::parseAndValidateCSV($csv);
      if (!$parsedCSV) break;
      Display::append("<table border=1>");
      foreach ($parsedCSV as $str)
      {
        Display::append("<tr>");
        foreach ($str as $field)
        {
          Display::append("<td>$field");
        }
      }
      Display::append("</table>");
      $dbConnectionShop = new DbSafeConnection($creds['db_name'], $creds['db_user'],
        $creds['db_pass'], Config::DB_HOST);
      ImportOperations::importCategories($dbConnectionShop, $parsedCSV);
      ImportOperations::importProducts($dbConnectionShop, $parsedCSV);
  }

  Display::show();

?>