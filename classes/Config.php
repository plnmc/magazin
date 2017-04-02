<?php
  class Config
  {
    const DEBUG = false;
    const LOGGING = true;
    const LOGFILE = "manage2/pln.log";

    const MAIN_DOMAIN = '';
    const PATH_TO_VHOSTS = "";
    const PATH_TO_DISTRO = "";
    const PATH_TO_BACKUPS = "";
    const VERSION = "1.4.0.17";
    const DB_HOST = 'localhost';
    const DB_NAME = 'main';
    const DB_USER = 'mainuser';
    const DB_PASS = '123qwemain';
    const SHOP_DB_AUTO_NAME = 'shopdb';
    const SHOP_DB_AUTO_USER = 'shopuser';
    const SUPPORT_EMAIL = '';
    const CONFIGFILE = "httpdocs/config/settings.inc.php";
    const SQL_DUMPS_DIR = "httpdocs/install/sql/";
    const DB_TABLE_PREFIX = "ps_";
    const MAP_CATEGORIES_TABLE = "import_map_categories";
    const MAP_PRODUCTS_TABLE = "import_map_products";
    const TIME_BUFFER_DAYS = 3; //buffer for billing date
    const OLD_SHOPS_PERIOD_MONTHS = 5; //period for old shops removal
    const SYSTEM_LOGIN = ""; //system staff member for shops

  }
?>
