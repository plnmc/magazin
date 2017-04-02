<?php
  session_start();
  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', '1');
  date_default_timezone_set('Asia/Novosibirsk');
  require_once("classes/autoload.php");

  $topMenuLinks = array(
        "create"=>"Создать магазин",
        "demo"=>"Демо",
        "clients"=>"Клиенты",
        "price"=>"Цены",
        "news"=>"Новости",
        "help"=>"Помощь",
        "contacts"=>"Контакты");

  function topMenu($links, $activeLink)
  {
    function markActiveLink($link, $activeLink)
    {
      if ($link == $activeLink)
      {
        return "class=\"top-active-link\" ";
      }
      return "";
    }

    $str = "";
    foreach ($links as $key=>$value)
    {
      $str .= "<a ".markActiveLink($key, $activeLink)." href=\"".$_SERVER["PHP_SELF"]."?link=$key\">$value</a>\n";
    }
    return $str;
  }

  DataProcessor::stripAll();
  $link = $_GET['link'];
  require "top.inc.php";
  if ( array_key_exists($link, $topMenuLinks) && file_exists($link.".inc.php") )
  {
    include $link.".inc.php";
  }
  else
  {    if ($link == "")
    {      include "index.inc.php";    }
    else
    {
      include "404.inc.php";
    }
  }
  require "bottom.inc.php";

?>