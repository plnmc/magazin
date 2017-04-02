<?php
  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', '1');
  date_default_timezone_set('Asia/Novosibirsk');

  require_once("../classes/autoload.php");

  DataProcessor::stripAll();

  $page = $_REQUEST['page'];
  $shopId = $_REQUEST['shop_id'];
  $operation = $_REQUEST['operation']; //add or sub
  $period = $_REQUEST['period']; //month or year
  $dbConnection = new DbSafeConnection();
  if ($dbConnection->changeNextBillDate($shopId, $operation, $period))
  {
    header("Location: index.php?page=$page&operation=read&shop_id=".$shopId);
  }
  else
  {    Display::append(sprintf(Strings::ERROR_CHANGE_BILL_DATE_SHOP_STATE, $shopId));
    Display::show();  }
?>