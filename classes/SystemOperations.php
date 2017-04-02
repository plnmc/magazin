<?php
  class SystemOperations
  {
    private static function addShopInDb($md5, $shopName, $name, $surname, $phone, $city, $pass, &$shopInfo)
    {
      $dbConnection = new DbSafeConnection();
      $status = $dbConnection->addShop($md5, $shopName, $name, $surname, $phone, $city, $pass, $shopInfo);
      if (!$status)
      {        Display::append(Strings::INSTALLER_ERROR_MAIN_DB_INSERT);
      }
      return $status;
    }

    private static function createSubdomain($shopInfo)
    {
      $status = PleskOperations::createSubdomain($shopInfo['subdomain']);
      if ($status != 0)
      {
        Display::append(Strings::INSTALLER_ERROR_SUBDOMAIN_CREATE);
      }
      return $status;
    }

    private static function createDatabase($shopInfo)
    {
      //get info about last free DB
      $status = PleskOperations::createDatabase($shopInfo['db_name'],
        $shopInfo['db_user'], $shopInfo['db_pass']);
      if ($status != 0)
      {
        Display::append(Strings::INSTALLER_ERROR_SHOP_DB_CREATE);
      }
      return $status;
    }

    private static function removeDatabase($dbName)
    {
      $status = PleskOperations::removeDatabase($dbName);
      if ($status != 0)
      {
        Display::append(Strings::INSTALLER_ERROR_SHOP_DB_REMOVE);
      }
      return $status;
    }

    private static function reCreateDatabase($shopInfo)
    {      $status = SystemOperations::removeDatabase($shopInfo['db_name']);
      if ($status != 0)
      {        return $status;      }
      $status = SystemOperations::createDatabase($shopInfo);
      return $status;
    }

    private static function copyShopDistro($shopInfo)
    {
      //copying files to subdomain vhost dir
      $commandToExecute = "sudo /sandbox/copy.sh -pr ".Config::PATH_TO_DISTRO.Config::VERSION."/* "
        .$shopInfo['path_to_subdomain']."/httpdocs/ 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0)
      {
        Display::append(Strings::INSTALLER_ERROR_FILE_COPY);
      }
      //copying file ".htaccess" separately due to linux limitations
      $commandToExecute = "sudo /sandbox/copy.sh -pr ".Config::PATH_TO_DISTRO.Config::VERSION."/.htaccess "
        .$shopInfo['path_to_subdomain']."/httpdocs/ 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0)
      {
        Display::append(Strings::INSTALLER_ERROR_FILE_COPY);
      }
      return $status;
    }

    private static function removeShopFiles($shopInfo)
    {
      $pathToShop = $shopInfo['path_to_subdomain']."/httpdocs/*";
      $commandToExecute = "sudo /sandbox/remove.sh -r ".$pathToShop." 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0) Display::append(Strings::INSTALLER_ERROR_SHOP_NOT_DELETED);
      return $status;
    }

    private static function createShopConfig($shopInfo)
    {      //get DB and dbuser and db password linked with subdomain
      $dbHost = $shopInfo['db_host'];
      $dbName = $shopInfo['db_name'];
      $dbUser = $shopInfo['db_user'];
      $dbPass = $shopInfo['db_pass'];
      $content =  "<?php\n"
        ."define('_DB_SERVER_', '$dbHost');\n"
        ."define('_DB_TYPE_', 'MySQL');\n"
        ."define('_DB_NAME_', '$dbName');\n"
        ."define('_DB_USER_', '$dbUser');\n"
        ."define('_DB_PASSWD_', '$dbPass');\n"
        ."define('_DB_PREFIX_', '".Config::DB_TABLE_PREFIX."');\n"
        ."define('_MYSQL_ENGINE_', 'InnoDB');\n"
        ."define('__PS_BASE_URI__', '/');\n"
        ."define('_PS_CACHING_SYSTEM_', 'MCached');\n"
        ."define('_PS_CACHE_ENABLED_', '0');\n"
        ."define('_MEDIA_SERVER_1_', '');\n"
        ."define('_MEDIA_SERVER_2_', '');\n"
        ."define('_MEDIA_SERVER_3_', '');\n"
        ."define('_THEME_NAME_', 'prestashop');\n"
        ."define('_COOKIE_KEY_', '".DataProcessor::generateRandomStringExtended(56)."');\n"
        ."define('_COOKIE_IV_', '".DataProcessor::generateRandomStringExtended(8)."');\n"
        ."define('_PS_CREATION_DATE_', '".date('Y-m-d')."');\n"
        ."define('_PS_VERSION_', '".Config::VERSION."');\n"
        ."?>";
      $filename = $shopInfo['path_to_subdomain']."/".Config::CONFIGFILE;
      if (!$handle = fopen($filename, 'w'))
      {
        Display::append(Strings::INSTALLER_ERROR_CONFIG_CREATE);
        return 1;
      }
      if (fwrite($handle, $content) === FALSE)
      {
        Display::append(Strings::INSTALLER_ERROR_CONFIG_WRITE);
        return 1;
      }
      fclose($handle);
      return 0;
    }

    private static function importSql($shopInfo)
    {      //get DB and dbuser and db password linked with subdomain
      $dbHost = $shopInfo['db_host'];
      $dbName = $shopInfo['db_name'];
      $dbUser = $shopInfo['db_user'];
      $dbPass = $shopInfo['db_pass'];
      $dbConnection = new DbSafeConnection($dbName, $dbUser, $dbPass, $dbHost);

      $sqlDumpsPath = $shopInfo['path_to_subdomain']."/".Config::SQL_DUMPS_DIR;

  		//from install/createDB.php
      $sqlFiles = array("shop.sql"); //"db.sql", "db_settings_lite.sql", "db_settings_extends.sql") ;
      foreach ($sqlFiles as $sqlFile)
      {
        $sqlFile = $sqlDumpsPath . $sqlFile;
        if (!file_exists($sqlFile))
        {
          Display::append(sprintf(Config::INSTALLER_ERROR_FILE_NOT_FOUND, $sqlFile));
          return 1;        }
  		  $sqlQueries = "";
		    $trimmedQueries = "";
        if ( !$sqlQueries .= file_get_contents($sqlFile) )
        {
          Display::append(sprintf(Config::INSTALLER_ERROR_FILE_NOT_OPENED, $sqlFile));
          return 1;
        }
        $sqlQueries = str_replace("PREFIX_", Config::DB_TABLE_PREFIX, $sqlQueries);
        $sqlQueries = preg_split("/;\s*[\r\n]+/", $sqlQueries);
        foreach ($sqlQueries as $query)
        {          $query = trim($query);
  			  if(!empty($query))
  			  {  			    $trimmedQueries[] = $query;
  				}
  			}
  			$success = $dbConnection->performTransaction($trimmedQueries);
  			if (!$success)
  			{  			  return 1;  			}
  		}
  		return 0;
		}

    private static function configureShop($shopInfo, $shopName, $name, $surname, $password)
    {
      //get DB and dbuser and db password linked with subdomain
      $dbHost = $shopInfo['db_host'];
      $dbName = $shopInfo['db_name'];
      $dbUser = $shopInfo['db_user'];
      $dbPass = $shopInfo['db_pass'];
      $dbConnection = new DbSafeConnection($dbName, $dbUser, $dbPass, $dbHost);

    	$res = $dbConnection->configureShop_1_4($shopInfo, $shopName, $name, $surname, $password);
    	return $res;
    }

    private static function renameInstallationDirs($shopInfo)
    {
      $pathToShop = $shopInfo['path_to_subdomain']."/httpdocs/";
      $pathToAdminDir = $pathToShop.$shopInfo['admin_dir'];
      $commandToExecute = "sudo /sandbox/move.sh ".$pathToShop."admin"
        ." ".$pathToAdminDir." 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0) Display::append(Strings::INSTALLER_ERROR_ADMIN_DIR_NOT_RENAMED);
      $commandToExecute = "sudo /sandbox/remove.sh -r ".$pathToShop."install"
        ." 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0) Display::append(Strings::INSTALLER_ERROR_INSTALL_DIR_NOT_DELETED);
      return $status;
    }

    public static function createShop($md5, $shopName, $name, $surname, $phone, $city, $password)
    {
      $shopInfo = null;
      $status = SystemOperations::addShopInDb($md5, $shopName, $name, $surname, $phone, $city,$password, $shopInfo);
      if (!$status) return false;
      $status = SystemOperations::createSubdomain($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::createDatabase($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::copyShopDistro($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::createShopConfig($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::importSql($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::configureShop($shopInfo, $shopName, $name, $surname, $password);
      if (!$status) return false;
      $status = SystemOperations::renameInstallationDirs($shopInfo);
      if ($status !=0) return false;
      return $shopInfo;
    }

    public static function executeCommand($command, &$output=null)
    {
      $status = null;
      exec($command, $output, $status);
      Display::debug("Executed command:\n".$command."\nOutput:\n"
        .implode("\n", $output)."\nStatus is:\n".$status);
      return $status;
    }

    public static function reCreateDemoShop()
    {
      $subDomain = "demo";
      $shopName = "Демо-магазин";
      $name = "Имя";
      $surname = "Фамилия";
      $email = "test@test.test";
      $password = "12345678";

      $shopInfo = null;
      $dbId = 51;
      $shopInfo['db_id'] = $dbId;
      $shopInfo['db_host'] = Config::DB_HOST;
      $shopInfo['db_name'] = "shopdb".$dbId;
      $shopInfo['db_user'] = "shopuser".$dbId;
      $shopInfo['db_pass'] = DataProcessor::generateRandomString(8);
      $shopInfo['admin_dir'] = "admin381";
      $shopInfo['domain'] = $subDomain.".".Config::MAIN_DOMAIN;
      $shopInfo['subdomain'] = $subDomain;
      $shopInfo['path_to_subdomain'] = Config::PATH_TO_VHOSTS."/".$subDomain;
      $shopInfo['email'] = $email;

      $status = SystemOperations::recreateDatabase($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::removeShopFiles($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::copyShopDistro($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::createShopConfig($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::importSql($shopInfo);
      if ($status !=0) return false;
      $status = SystemOperations::configureShop($shopInfo, $shopName, $name, $surname, $password);
      if (!$status) return false;
      $status = SystemOperations::renameInstallationDirs($shopInfo);
      if ($status !=0) return false;
      return $true;
    }

    public function setShopState($shopId, $state)
    {
      $dbConnection = new DbSafeConnection();
      $dbConnectionShop = $dbConnection->getShopDbConnection($shopId);
      $result1 = $dbConnection->setShopStateInMainDb($shopId, $state);
      $result2 = $dbConnectionShop->setShopStateInShopDB($state);
      return ($result1 and $result2);
    }

    public function setShopAliveState($shopId, $state)
    {

      $dbConnection = new DbSafeConnection();
      $subDomain = $dbConnection->getSubDomainNameByShopId($shopId);
      if ($state == 0)
      {
        $result = SystemOperations::backupSubdomainWithDelete($subDomain);
      }
      else
      {
        $result = SystemOperations::restoreSubdomainWithCreate($subDomain);
      }
      if ($result != 0) return false;

      $dbConnection = new DbSafeConnection();
      $result = $dbConnection->setShopAliveState($shopId, $state);

      return $result;
    }

    public function getClientList()
    {
      $dbConnection = new DbSafeConnection();
      $clients = $dbConnection->getClients();
      return $clients;
    }

    public static function getNewSystemPassword($shopId)
    {
      //get DB and dbuser and db password linked with subdomain
      $cookieKey = self::getCookieKey($shopId);
      $dbConnection = new DbSafeConnection();
      $dbConnectionShop = $dbConnection->getShopDbConnection($shopId);
    	$password = $dbConnectionShop->resetSystemEmployee($cookieKey);
    	return $password;
    }

    private static function getCookieKey($shopId)
    {
  		//from install/checkShopInfos.php
      $dbConnection = new DbSafeConnection();
  		$filename = $dbConnection->getShopPath($shopId)."/".Config::CONFIGFILE;
  		$config = file_get_contents($filename);
  		$index = strpos($config, "_COOKIE_KEY_', '") + 16;
  		$cookieKey = substr($config, $index, 56);
      return $cookieKey;
    }

    public static function sendReminderLetterToAll()
    {
      $dbConnection = new DbSafeConnection();
      $shopIds = $dbConnection->getShopIdsToRemind();
      if (!$shopIds) return;
      foreach ($shopIds as $shopId)
      {        self::sendReminderLetter($shopId);
        echo $shopId."\n";      }
    }

    public static function sendReminderLetter($shopId)
    {
      $dbConnection = new DbSafeConnection();
      $email = $dbConnection->getStaffEmailByShopId($shopId);
      $mainDomainName = $dbConnection->getMainDomainNameByShopId($shopId);
      $nextBillDate = $dbConnection->getNextBillDateByShopId($shopId);
      $subject = sprintf(Strings::EMAIL_SUBJECT, $mainDomainName);
      $body = sprintf(nl2br(Strings::EMAIL_REMINDER), $mainDomainName, $nextBillDate);
      DataProcessor::sendMail($email, $subject, $body);
      $body = "<p><b>".Strings::COPY_TO_ADMIN_SIMPLE."</b><hr>".$body;
      DataProcessor::sendMail(Config::SUPPORT_EMAIL, $subject."(".Strings::COPY_TO_ADMIN_SIMPLE.")", $body);
    }

    //backup to folder /sandbox/backups
    private static function backupSubdomain($subDomain)
    {      if (!Validator::isSubDomain($subDomain, Strings::LENGTH_SUBDOMAIN_NAME, Strings::INCORRECT_SUBDOMAIN_NAME)) return -1;
      //create new backup
      $commandToExecute = "sudo /sandbox/tar.sh -C \"".Config::PATH_TO_VHOSTS."\" -cpzf \"".Config::PATH_TO_BACKUPS
        .$subDomain."___".date("Ymd_H-i-s").".tgz\" \"".$subDomain."\" 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0) Display::appendError("Error during backup!");
      return $status;
    }

    //get filename of last backup
    private static function getLastSubdomainBackup($subDomain)
    {
      $filename = null;
      if (!Validator::isSubDomain($subDomain, Strings::LENGTH_SUBDOMAIN_NAME, Strings::INCORRECT_SUBDOMAIN_NAME)) return "";
      $commandToExecute = "sudo /sandbox/find.sh \"".$subDomain."___*.tgz\" 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute, $filename);
      if ($filename[0] == "")
      {
        Display::appendError("Backups not found!");
      }
      return $filename[0];
    }

    //restore subdomain from specified tgz file
    //if filename is empty - restore from last backup
    private static function restoreSubdomain($subDomain, $filename="")
    {
      if (!Validator::isSubDomain($subDomain, Strings::LENGTH_SUBDOMAIN_NAME, Strings::INCORRECT_SUBDOMAIN_NAME)) return -1;
      if ($filename == "")
      {        $filename = SystemOperations::getLastSubdomainBackup($subDomain);
        if ($filename == "") return -1;
      }
      $commandToExecute = "sudo /sandbox/tar.sh -C \"".Config::PATH_TO_VHOSTS."\" -xf \"".$filename."\" 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status != 0) Display::appendError("Error during restoring from backup!");
      return $status;
    }

    private static function backupSubdomainWithDelete($subDomain)
    {
      $status = SystemOperations::backupSubdomain($subDomain);
      if ($status != 0) return $status;
      $status = PleskOperations::removeSubdomain($subDomain);
      if ($status != 0) Display::appendError("Error during subdomain removal!");
      return $status;
    }

    private static function restoreSubdomainWithCreate($subDomain)
    {
      $status = PleskOperations::createSubdomain($subDomain);
      if ($status != 0)
      {        Display::appendError("Error during subdomain creation!");
        return $status;
      }
      $status = SystemOperations::restoreSubdomain($subDomain);
      return $status;
    }
  }
?>