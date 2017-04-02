<?php
  // Functions from this class should use DbConnection::safeExecute to execute queries
  class DbSafeConnection extends DbConnection
  {
    function __construct($dbName = Config::DB_NAME, $dbUser = Config::DB_USER,
      $dbPass = Config::DB_PASS, $dbHost = Config::DB_HOST)
    {
      parent:: __construct($dbName, $dbUser, $dbPass, $dbHost);
    }

    public function getShopDbConnection($shopId)
    {
      $query = "select db_id from shops where shops.shop_id = ".$shopId;
      $dbId = $this->getSingleValue($query);
      if ($dbId == null)
      {
        return false;
      }
      $creds = $this->getDbCredentialsById($dbId);
      $dbConnectionShop = new DbSafeConnection($creds['db_name'], $creds['db_user'],
        $creds['db_pass'], Config::DB_HOST);
      return $dbConnectionShop;    }

    /*
     * to use only with SELECTs, not for INSERTs etc.
     * returns value of 0th field in result, if SELECT query was executed successfully
     * or null if select gives empty reslut
     */
    public function getSingleValue($query)
    {
      $result = null;
      $this->safeExecute($query, $result);
      //if select gives empty value
      if ($result == null)
      {
        return null;
      }
      return current($result[0]); // if query was "select" then this will be 0th value
    }

    public function getNextAutoIncrement($tableName)
    {
      $tableName = mysql_real_escape_string($tableName);
      $query = "SHOW TABLE STATUS LIKE '$tableName'";
      $this->safeExecute($query, $result);
      $nextIncrement = $result[0]['Auto_increment'];
      return $nextIncrement;
    }

    public function getRowsCount($tableName)
    {
      $query = "select count(*) from $tableName";
      return $this->getSingleValue($query);
    }

    public function isDomainTaken($domainName)
    {
      $query = "select count(*) from domain_names where domain_name = '%s'";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($domainName)));
      if ($value > 0)
      {
        return true;
      }
      return false;
    }

    //check if fresh (non-expired) subdomain request for specified email exists
    // fresh - means 1 day or less
    //$subdomain - local part of subdomain (until first dot)
    public function isFreshSubDomainRequestExists($subDomain, $email)
    {
      $query = "select count(*) from domain_requests where domain_name = '%s' and email = '%s'"
       ." and (DATE_ADD(requested_date, INTERVAL 1 DAY) > NOW())";
      $value = $this->getSingleValue(sprintf($query,
        mysql_real_escape_string($subDomain . "." . Config::MAIN_DOMAIN),
        mysql_real_escape_string($email)));
      if ($value > 0)
      {
        return true;
      }
      return false;
    }

    //get request_id by MD5
    public function getDomainRequestIdByMD5($md5)
    {
      $query = "select request_id from domain_requests where md5 = '%s'";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($md5)));
      return $value;
    }

    //get domain request name by MD5. Return empty string if not found
    public function getDomainRequestNameByMD5($md5)
    {
      $query = "select domain_name from domain_requests where md5 = '%s'";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($md5)));
      return $value;
    }

    //get domain request email by MD5. Return empty string if not found
    public function getDomainRequestEmailByMD5($md5)
    {
      $query = "select email from domain_requests where md5 = '%s'";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($md5)));
      return $value;
    }

    //if request with specified MD5 is not expired - return request_id, else return empty string
    public function getFreshDomainRequestIdByMD5($md5)
    {
      $query = "select request_id from domain_requests where md5 = '%s'"
       ." and (DATE_ADD(requested_date, INTERVAL 1 DAY) > NOW())";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($md5)));
      return $value;
    }

    public function getMainDomainNameByShopId($shopId)
    {
      $query = "select domain_names.domain_name from shops, domain_names where shops.shop_id = domain_names.shop_id and domain_names.domain_type_id = 1 and shops.shop_id = %s";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($shopId)));
      return $value;
    }

    public function getSubDomainNameByShopId($shopId)
    {
      $value = $this->getMainDomainNameByShopId($shopId);
      return str_replace(".".Config::MAIN_DOMAIN, "", $value);
    }

    public function getClients()
    {
      $query = "select UNIX_TIMESTAMP(shops.created_date) as created_date, domain_names.domain_name as domain_name, shops.shop_name as shop_name from shops, domain_names where shops.shop_id = domain_names.shop_id and domain_names.domain_type_id = 1 and shops.show_in_clients=1 and shops.shop_state_id=1 order by shops.created_date asc";
      if ($this->safeExecute($query, $result))
        return $result;      return false;
    }

    //function takes email of 1st staff member from shop DB
    public function getStaffEmailByShopId($shopId)
    {
      $dbConnectionShop = $this->getShopDbConnection($shopId);
      return $dbConnectionShop->getStaffEmail();
    }

    //add subdomain request
    //$subdomain - local part of subdomain (until first dot)
    // returns md5 of new domain request if it was successfully added, or false in another case
    public function addSubDomainRequest($subDomain, $email)
    {
      $id = $this->getNextAutoIncrement("domain_requests");
      $cryptedString = DataProcessor::generateMD5(array($id, $subDomain. "." . Config::MAIN_DOMAIN, $email));

      $query = "insert into domain_requests (request_id, domain_name, email, requested_date, md5)"
       ." values (NULL, '%s', '%s', NOW(), '%s')";
      $success = $this->safeExecute(sprintf($query,
        mysql_real_escape_string($subDomain. "." . Config::MAIN_DOMAIN),
        mysql_real_escape_string($email),
        mysql_real_escape_string($cryptedString)));
      if ($success)
      {
        return $cryptedString;
      }
      return false;
    }

    public function addShop($md5, $shopName, $name, $surname, $phone, $city, $password, &$shopInfo)
    {
      $domain = $this->getDomainRequestNameByMD5($md5);
      $email = $this->getDomainRequestEmailByMD5($md5);
      $requestId = $this->getDomainRequestIdByMD5($md5);

      $subDomain = substr($domain, 0, strpos($domain, "."));
      $dbId = $this->getNextAutoIncrement("dbs");
      $shopId = $this->getNextAutoIncrement("shops");
      $contactId = $this->getNextAutoIncrement("contacts");

      $shopInfo['db_id'] = $dbId;
      $shopInfo['db_host'] = Config::DB_HOST;
      $shopInfo['db_name'] = Config::SHOP_DB_AUTO_NAME.$dbId;
      $shopInfo['db_user'] = Config::SHOP_DB_AUTO_USER.$dbId;
      $shopInfo['db_pass'] = DataProcessor::generateRandomString(8);
      $shopInfo['admin_dir'] = "admin".rand(0, 1000);
      $shopInfo['domain'] = $domain;        // subxxx.vsemagazin.ru
      $shopInfo['subdomain'] = $subDomain;  // subxxx
      $shopInfo['path_to_subdomain'] = Config::PATH_TO_VHOSTS."/".$subDomain;
      $shopInfo['email'] = $email;

      $query = "insert into dbs (db_id, name, host, user, password, db_status_id, created_date, modified_date)"
        ." values (NULL, '%s', '%s', '%s', '%s', %d, NOW(), NULL)";
      $queriesArray[] = sprintf($query,
        mysql_real_escape_string($shopInfo['db_name']),
        mysql_real_escape_string($shopInfo['db_host']),
        mysql_real_escape_string($shopInfo['db_user']),
        mysql_real_escape_string($shopInfo['db_pass']),
        1);

      $query = "insert into shops (shop_id, shop_state_id, shop_name, email, password, db_id, path_shop, path_admin, created_date, modified_date, next_bill_date)"
        ." values (NULL, %d, '%s', '%s', '%s', %d, '%s', '%s', NOW(), NULL, DATE_ADD(NOW(), INTERVAL 1 WEEK ))";
      $queriesArray[] = sprintf($query,
        1,
        mysql_real_escape_string($shopName),
        mysql_real_escape_string($email),
        mysql_real_escape_string($password),
        mysql_real_escape_string($shopInfo['db_id']),
        mysql_real_escape_string($shopInfo['path_to_subdomain']),
        mysql_real_escape_string($shopInfo['admin_dir']));

      $query = "insert into domain_names (domain_name_id, request_id, shop_id, domain_name, domain_type_id, domain_state_id, created_date, modified_date)"
        ." values (NULL, %d, %d, '%s', %d, %d, NOW(), NULL)";
      $queriesArray[] = sprintf($query,
        $requestId,
        $shopId,
        mysql_real_escape_string($shopInfo['domain']),
        1,
        1);

      $query = "insert into contacts (contact_id, name, surname, email, fathername, organization, position, phones, info, created_date, modified_date)"
        ." values (NULL, '%s', '%s', '%s', '', '', '', '%s', '%s', NOW(), NULL)";
      $queriesArray[] = sprintf($query,
        mysql_real_escape_string($name),
        mysql_real_escape_string($surname),
        mysql_real_escape_string($email),
        mysql_real_escape_string($phone),
        mysql_real_escape_string($city));

      $query = "insert into shop_contacts (shop_id, contact_id)"
        ." values (%d, %d)";
      $queriesArray[] = sprintf($query,
        $shopId,
        $contactId);

      return $this->performTransaction($queriesArray);
    }

    public function getDbCredentialsById($dbId)
    {      $query = "select name, host, user, password from dbs where db_id = '%s'";
      $this->safeExecute(sprintf($query, mysql_real_escape_string($dbId)), $result);
      $ret["db_name"]= $result[0]["name"];
      $ret["db_host"]= $result[0]["host"];
      $ret["db_user"]= $result[0]["user"];
      $ret["db_pass"]= $result[0]["password"];
      return $ret;    }

    public function setShopStateInMainDB($shopId, $state)
    {
      if (($state != 0) and ($state !=1)) return false;
      $query = "update shops set shop_state_id = '%s' where shop_id = '%s'";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($state), mysql_real_escape_string($shopId)));
    }

    public function setShopAliveState($shopId, $state)
    {
      if (($state != 0) and ($state !=1)) return false;
      $query = "update shops set shop_alive = '%s' where shop_id = '%s'";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($state), mysql_real_escape_string($shopId)));
    }

    public function getShopState($shopId)
    {
      $query = "select shop_state_id from shops where shop_id = '%s'";
      return $this->getSingleValue(sprintf($query, mysql_real_escape_string($shopId)));
    }

    public function getShopAliveState($shopId)
    {
      $query = "select shop_alive from shops where shop_id = '%s'";
      return $this->getSingleValue(sprintf($query, mysql_real_escape_string($shopId)));
    }

    public function getShopPath($shopId)
    {
      $query = "select path_shop from shops where shop_id = '%s'";
      return $this->getSingleValue(sprintf($query, mysql_real_escape_string($shopId)));
    }

    public function getNews($start, $quantity)
    {
      $limit = "limit $start, $quantity";
      $query = "select * from news order by news_date desc $limit";
      return $this->safeExecute($query);
    }

    // Function adds or subtracts 1 month or 1 year from next bill date
    // operation: add or sub
    // period: month or year
    public function changeNextBillDate($shopId, $operation, $period)
    {
      switch ($operation)
      {
        case "add": $operationSql = "DATE_ADD";
                    break;
        case "sub": $operationSql = "DATE_SUB";
                    break;
        default: return false;
      }
      switch ($period)
      {
        case "day": $periodSql = "DAY";
                    break;
        case "month": $periodSql = "MONTH";
                    break;
        case "year": $periodSql = "YEAR";
                    break;
        default: return false;
      }
      $query = "update shops set next_bill_date = ".$operationSql."(next_bill_date, INTERVAL 1 ".$periodSql.") where shop_id = '%s'";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($shopId)));
    }

    public function getShopType($shopId)
    {
      $query = "select shop_type_id from shops where shop_id='%s'";
      return $this->getSingleValue(sprintf($query, mysql_real_escape_string($shopId)));
    }

    public function setShopType($shopId, $shopType)
    {
      $query = "update shops set shop_type_id = '%s' where shop_id = '%s'";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($shopType), mysql_real_escape_string($shopId)));
    }

    public function toggleShowInClients($shopId)
    {      $query = "update shops set show_in_clients = (1-show_in_clients) where shop_id = '%s'";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($shopId)));    }

   ////////////////////////////////////////////////
   /// IMPORT
   ////////////////////////////////////////////////

    public function disableMappedCategories()
    {
      $query = "update ".Config::DB_TABLE_PREFIX."category set active = 0 where id_parent <> 0 and "
        ."id_category in (select cat_id from ".Config::DB_TABLE_PREFIX.Config::MAP_CATEGORIES_TABLE.")";
      return $this->safeExecute($query);
    }

    public function disableMappedProducts()
    {
      $query = "update ".Config::DB_TABLE_PREFIX."product set active = 0 where "
        ."id_product in (select product_id from ".Config::DB_TABLE_PREFIX.Config::MAP_PRODUCTS_TABLE.")";
      return $this->safeExecute($query);
    }

    public function getMaxCategoryId()
    {
      $query = "select max(id_category) as max from ".Config::DB_TABLE_PREFIX."category";
      return $this->getSingleValue($query);
    }

    public function getMaxProductId()
    {
      $query = "select max(id_product) as max from ".Config::DB_TABLE_PREFIX."product";
      return $this->getSingleValue($query);
    }

    public function getCategoryIdByKey($key)
    {
      $query = "select cat_id from ".Config::DB_TABLE_PREFIX.Config::MAP_CATEGORIES_TABLE
        ." where ext_key='%s'";
      return $this->getSingleValue(sprintf($query, mysql_real_escape_string($key)));
    }

    public function getProductIdByKey($key)
    {
      $query = "select product_id from ".Config::DB_TABLE_PREFIX.Config::MAP_PRODUCTS_TABLE
        ." where ext_key='%s'";
      return $this->getSingleValue(sprintf($query, mysql_real_escape_string($key)));
    }

    public function insertCategoryMapping($key, $catId)
    {
      $query = "insert into ".Config::DB_TABLE_PREFIX.Config::MAP_CATEGORIES_TABLE
        ." (id, ext_key, cat_id) values (NULL, '%s', '%s')";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($key), mysql_real_escape_string($catId)));
    }

    public function insertProductMapping($key, $productId)
    {
      $query = "insert into ".Config::DB_TABLE_PREFIX.Config::MAP_PRODUCTS_TABLE
        ." (id, ext_key, product_id) values (NULL, '%s', '%s')";
      return $this->safeExecute(sprintf($query, mysql_real_escape_string($key), mysql_real_escape_string($productId)));
    }

    public function isCategoryExistsById($categoryId)
    {
      $query = "select count(*) from ".Config::DB_TABLE_PREFIX."category"
        ." where id_category = '%s'";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($categoryId)));
      if ($value > 0)
      {
        return true;
      }
      return false;    }

    public function isProductExistsById($productId)
    {
      $query = "select count(*) from ".Config::DB_TABLE_PREFIX."product"
        ." where id_product = '%s'";
      $value = $this->getSingleValue(sprintf($query, mysql_real_escape_string($productId)));
      if ($value > 0)
      {
        return true;
      }
      return false;
    }

    public function updateCategory($categoryId, $name, $parent, $depth, $link)
    {
      $queries[] = "update ".Config::DB_TABLE_PREFIX."category set"
        ." id_parent = '".mysql_real_escape_string($parent)."',"
        ." level_depth = '".mysql_real_escape_string($depth)."',"
        ." active = 1, date_upd = NOW()"
        ." where id_category = '".mysql_real_escape_string($categoryId)."'";

      $queries[] = "update ".Config::DB_TABLE_PREFIX."category_lang set"
        ." name = '".mysql_real_escape_string($name)."',"
        ." link_rewrite = '".mysql_real_escape_string($link)."'"
        ." where id_category = '".mysql_real_escape_string($categoryId)."'";
      return $this->performTransaction($queries);
    }

    public function updateProduct($productId, $name, $parent, $price, $link, $quantity = null)
    {
      $queries[] = "update ".Config::DB_TABLE_PREFIX."category_product set"
        ." id_category = '".mysql_real_escape_string($parent)."'"
        ." where id_product = '".mysql_real_escape_string($productId)."' limit 1";
      // limit 1 - needed because product can be in several categories
      // if quantity == null - do not change quantity of product
      $strQuantity = "";
      if ($quantity != null)
      {        $strQuantity = ", quantity = '".mysql_real_escape_string($quantity)."'";
      }

      $queries[] = "update ".Config::DB_TABLE_PREFIX."product set"
        ." id_category_default = '".mysql_real_escape_string($parent)."',"
        ." price = '".mysql_real_escape_string($price)."',"
        ." active = 1".$strQuantity
        .", date_upd = NOW() where id_product = '".mysql_real_escape_string($productId)."'";

      $queries[] = "update ".Config::DB_TABLE_PREFIX."product_lang set"
        ." link_rewrite = '".mysql_real_escape_string($link)."',"
        ." name = '".mysql_real_escape_string($name)."'"
        ." where id_product = '".mysql_real_escape_string($productId)."'";
      return $this->performTransaction($queries);
    }

    public function insertCategory($categoryId, $name, $parent, $depth, $link)
    {
      $position = $this->getCategoryLastPosition($parent);
      $query = "select id_lang from ".Config::DB_TABLE_PREFIX."lang";
      $this->safeExecute($query, $languages);

      $queries[] = "insert into ".Config::DB_TABLE_PREFIX."category"
        ." (id_category, id_parent, level_depth, nleft, nright, active, date_add, date_upd, position)"
        ." values ('".mysql_real_escape_string($categoryId)."', '"
        .mysql_real_escape_string($parent)."', '"
        .mysql_real_escape_string($depth)."', 0, 0, 1, NOW(), NOW(), '"
        .mysql_real_escape_string($position)."')";
      $queries[] = "insert into ".Config::DB_TABLE_PREFIX."category_group (id_category, id_group)"
        ." values ('".mysql_real_escape_string($categoryId)."', 1)";
      foreach ($languages as $lang)
      {
        $queries[] = "insert into ".Config::DB_TABLE_PREFIX."category_lang"
          ." (id_category, id_lang, name, description, link_rewrite, meta_title, meta_keywords, meta_description)"
          ." values ('".mysql_real_escape_string($categoryId)."', '".mysql_real_escape_string($lang['id_lang'])."',"
          ." '".mysql_real_escape_string($name)."', '', '".mysql_real_escape_string($link)."', '', '', '')";
      }
      return $this->performTransaction($queries);
    }

    public function insertProduct($productId, $name, $parent, $price, $link, $quantity = 0)
    {
      $position = $this->getProductLastPosition($parent);
      $query = "select id_lang from ".Config::DB_TABLE_PREFIX."lang";
      $this->safeExecute($query, $languages);

      $queries[] = "insert into ".Config::DB_TABLE_PREFIX."product "
        ."(id_product, id_supplier, id_manufacturer, id_tax_rules_group, id_category_default, "
        ."id_color_default, on_sale, online_only, ean13, upc, ecotax, quantity, minimal_quantity, "
        ."price, wholesale_price, unity, unit_price_ratio, additional_shipping_cost, reference, "
        ."supplier_reference, location, width, height, depth, weight, out_of_stock, quantity_discount, "
        ."customizable, uploadable_files, text_fields, active, available_for_order, "
        ."show_price, indexed, cache_is_pack, cache_has_attachments, cache_default_attribute, "
        ."date_add, date_upd) "
        ."values ('".mysql_real_escape_string($productId)."', 0, 0, 177, '".mysql_real_escape_string($parent)
        ."', 0, 0, 0, '', '', 0, '".mysql_real_escape_string($quantity)."', 1, '"
        .mysql_real_escape_string($price)."', 0, '', 0, 0, '', "
        ."'', '', 0, 0, 0, 0, 2, 0, "
        ."0, 0, 0, 1, 1, "
        ."1, 1, 0, 0, 0, "
        ."NOW(), NOW())";
      $queries[] = "insert into ".Config::DB_TABLE_PREFIX."category_product "
        ."(id_category, id_product, position)"
        ." values ('"
        .mysql_real_escape_string($parent)."', '"
        .mysql_real_escape_string($productId)."', '"
        .mysql_real_escape_string($position)."')";
      foreach ($languages as $lang)
      {
        $queries[] = "insert into ".Config::DB_TABLE_PREFIX."product_lang "
          ."(id_product, id_lang, description, description_short, link_rewrite, meta_description, "
          ."meta_keywords, meta_title, name, available_now, available_later)"
          ." values ('".mysql_real_escape_string($productId)."', '".mysql_real_escape_string($lang['id_lang'])."',"
          ."'', '', '".mysql_real_escape_string($link)."', '', '', '', '".mysql_real_escape_string($name)."', '', '')";
      }
      return $this->performTransaction($queries);

    }

	  public function getCategoryLastPosition($parentCategoryId)
	  {
	    $query = "SELECT MAX(position)+1 FROM ".Config::DB_TABLE_PREFIX."category WHERE id_parent = '%s'";
		  return $this->getSingleValue(sprintf($query, mysql_real_escape_string($parentCategoryId)));
  	}

	  public function getProductLastPosition($parentCategoryId)
	  {
	    $query = "SELECT MAX(position)+1 FROM ".Config::DB_TABLE_PREFIX."category_product WHERE id_category = '%s'";
		  return $this->getSingleValue(sprintf($query, mysql_real_escape_string($parentCategoryId)));
  	}

    public function isTableExists($tableName)
    {
      $query = "show tables like '%s'";
      $this->safeExecute(sprintf($query, mysql_real_escape_string($tableName)), $result);
      if (count($result) == 1)
      {        return true;
      }
      return false;
    }

    public function createImportTablesIfNotExist()
    {
      $queries[] = "
        CREATE TABLE IF NOT EXISTS `".Config::DB_TABLE_PREFIX.Config::MAP_CATEGORIES_TABLE."` (
          `id` int(10) unsigned NOT NULL auto_increment,
          `ext_key` varchar(256) NOT NULL,
          `cat_id` int(10) unsigned NOT NULL,
          PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
      ";
      $queries[] = "
        CREATE TABLE IF NOT EXISTS `".Config::DB_TABLE_PREFIX.Config::MAP_PRODUCTS_TABLE."` (
          `id` int(10) unsigned NOT NULL auto_increment,
          `ext_key` varchar(256) NOT NULL,
          `product_id` int(10) unsigned NOT NULL,
          PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
      ";
      return $this->performTransaction($queries);
    }

    ////////////////////////////////////////////////
    ///  functions which work with Prestashop DB
    ////////////////////////////////////////////////

    public function configureShop_1_3($shopInfo, $shopName, $name, $surname, $password)
    {
  		//from install/checkShopInfos.php
  		$filename = $shopInfo['path_to_subdomain']."/".Config::CONFIGFILE;
  		$config = file_get_contents($filename);
  		$index = strpos($config, "_COOKIE_KEY_', '") + 16;
  		$cookieKey = substr($config, $index, 56);

    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_NAME', '".mysql_real_escape_string($shopName)."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_EMAIL', '".mysql_real_escape_string($shopInfo['email'])."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_MAIL_METHOD', '1', NOW(), NOW())";
    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = (SELECT id_lang FROM '.Config::DB_TABLE_PREFIX.'lang WHERE iso_code = \'ru\') WHERE name = \'PS_LANG_DEFAULT\'';
    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'ru\' WHERE name = \'PS_LOCALE_LANGUAGE\'';
    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = (SELECT id_country FROM	'.Config::DB_TABLE_PREFIX.'country where iso_code = \'ru\') WHERE name = \'PS_COUNTRY_DEFAULT\'';
    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'RU\' WHERE name = \'PS_LOCALE_COUNTRY\'';
    	$queries[] = 'INSERT INTO '.Config::DB_TABLE_PREFIX.'employee (id_employee, lastname, firstname, email, passwd, last_passwd_gen, active, id_profile) VALUES (NULL, \''.mysql_real_escape_string(DataProcessor::strtoupper($surname))
    	 .'\', \''.mysql_real_escape_string(DataProcessor::ucfirst($name)).'\', \''.mysql_real_escape_string($shopInfo['email']).'\', \''
    	 .md5($cookieKey.$password).'\', \''.date('Y-m-d h:i:s', strtotime('-360 minutes')).'\', 1, 1)';
    	$queries[] = 'INSERT INTO '.Config::DB_TABLE_PREFIX.'contact (id_contact, email) VALUES (NULL, \''
    	  .mysql_real_escape_string($shopInfo['email']).'\'), (NULL, \''.mysql_real_escape_string($shopInfo['email']).'\')';

      return $this->performTransaction($queries);
    }

    public function configureShop_1_4($shopInfo, $shopName, $name, $surname, $password)
    {
  		//from install/checkShopInfos.php
  		$filename = $shopInfo['path_to_subdomain']."/".Config::CONFIGFILE;
  		$config = file_get_contents($filename);
  		$index = strpos($config, "_COOKIE_KEY_', '") + 16;
  		$cookieKey = substr($config, $index, 56);

    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('MA_MERCHANT_MAILS', '".mysql_real_escape_string($shopInfo['email'])."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_DOMAIN', '".mysql_real_escape_string($shopInfo['domain'])."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_DOMAIN_SSL', '".mysql_real_escape_string($shopInfo['domain'])."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_INSTALL_VERSION', '".Config::VERSION."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_NAME', '".mysql_real_escape_string($shopName)."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_EMAIL', '".mysql_real_escape_string($shopInfo['email'])."', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_MAIL_METHOD', '1', NOW(), NOW())";
    	$queries[] = "INSERT INTO ".Config::DB_TABLE_PREFIX."configuration (name, value, date_add, date_upd) VALUES ('PS_SHOP_ACTIVITY', '0', NOW(), NOW())";

//    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'ru\' WHERE name = \'PS_LOCALE_LANGUAGE\'';
//    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'0\' WHERE name = \'PS_CATALOG_MODE\'';
//    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'177\' WHERE name = \'PS_COUNTRY_DEFAULT\'';
//    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = "'.mysql_real_escape_string("Asia/Novosibirsk").'" WHERE name = \'PS_TIMEZONE\'';
//    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'RU\' WHERE name = \'PS_LOCALE_COUNTRY\'';
//    	$queries[] = 'UPDATE '.Config::DB_TABLE_PREFIX.'configuration SET value = \'6\' WHERE name = \'PS_LANG_DEFAULT\'';
    	$queries[] = 'INSERT INTO '.Config::DB_TABLE_PREFIX.'employee (id_employee, lastname, firstname, email, passwd, last_passwd_gen, bo_theme, active, id_profile, id_lang) VALUES (NULL, \''.mysql_real_escape_string(DataProcessor::strtoupper($surname))
    	 .'\', \''.mysql_real_escape_string(DataProcessor::ucfirst($name)).'\', \''.mysql_real_escape_string($shopInfo['email']).'\', \''
    	 .md5($cookieKey.$password).'\', \''.date('Y-m-d h:i:s', strtotime('-360 minutes')).'\', \'oldschool\', 1, 1, 6)';
    	$queries[] = 'INSERT INTO '.Config::DB_TABLE_PREFIX.'contact (id_contact, email, customer_service) VALUES (NULL, \''
    	  .mysql_real_escape_string($shopInfo['email']).'\', 1), (NULL, \''.mysql_real_escape_string($shopInfo['email']).'\', 1)';
      return $this->performTransaction($queries);

    }

    public function setShopStateInShopDB($state)
    {
      if (($state != 0) and ($state !=1)) return false;
      $queries[] = "update ".Config::DB_TABLE_PREFIX."configuration set value = '"
        .mysql_real_escape_string($state)."', date_upd = NOW()"
        ." where name = 'PS_SHOP_ENABLE'";
      $queries[] = "update ".Config::DB_TABLE_PREFIX."employee set active = '"
        .mysql_real_escape_string($state)."'";
      return $this->performTransaction($queries);
    }

    public function getProductsNumber()
    {
      $query = "select count(*) from ".Config::DB_TABLE_PREFIX."product";
      return $this->getSingleValue($query);
    }

    public function getStaffEmail()
    {
      $query = "select email from ".Config::DB_TABLE_PREFIX."employee order by id_employee limit 1";
      $value = $this->getSingleValue($query);
      return $value;
    }

    public function getNextBillDate($shopSubDomain)
    {
      $domainName = $shopSubDomain.".vsemagazin.ru";
      $query = "select UNIX_TIMESTAMP(shops.next_bill_date) as next_bill_date from shops, domain_names where domain_names.domain_name='"
        .mysql_real_escape_string($domainName)."' and domain_names.shop_id=shops.shop_id";
      $value = $this->getSingleValue($query);
      $value = date("d.m.Y", $value);
      return $value;
    }

    public function getNextBillDateByShopId($shopId)
    {
      $query = "select UNIX_TIMESTAMP(shops.next_bill_date) as next_bill_date from shops where shop_id='".mysql_real_escape_string($shopId)."'";
      $value = $this->getSingleValue($query);
      $value = date("d.m.Y", $value);
      return $value;
    }

    public function getEmployeeIdByEmail($email)
    {
      $query = "select id_employee from ".Config::DB_TABLE_PREFIX."employee where email='".mysql_real_escape_string($email)."'";
      $value = $this->getSingleValue($query);
      return $value;
    }

    //function adds system login to shop (
    //if system login exists, function updates it with new password and restore other settings
    public function resetSystemEmployee($cookieKey)
    {
      $firstName = "System";
      $lastName = "LOGIN";
      $email = Config::SYSTEM_LOGIN;
      $password = DataProcessor::generateRandomString(12);
      $employeeId = $this->getEmployeeIdByEmail($email);
      if ($employeeId != null)      {     	  $query = 'UPDATE '.Config::DB_TABLE_PREFIX.'employee set
          lastname=\''.$lastName.'\',
          firstname=\''.$firstName.'\',
          email=\''.$email.'\',
          passwd=\''.md5($cookieKey.$password).'\',
          last_passwd_gen=\''.date('Y-m-d h:i:s', strtotime('-360 minutes')).'\',
          bo_theme=\'oldschool\',
          active=1,
          id_profile=1,
          id_lang=6
          WHERE id_employee = '.$employeeId;
      }
      else
      {
     	  $query = 'INSERT INTO '.Config::DB_TABLE_PREFIX.'employee (id_employee, lastname, firstname, email, passwd, last_passwd_gen, bo_theme, active, id_profile, id_lang) VALUES (NULL, \''
          .$lastName.'\', \''.$firstName.'\', \''.$email.'\', \''.md5($cookieKey.$password).'\', \''.date('Y-m-d h:i:s', strtotime('-360 minutes')).'\', \'oldschool\', 1, 1, 6)';
      }
      $this->safeExecute($query);
      return $password;
    }

    public function getShopIdsToRemind()
    {
      $query = "select shop_id from shops where DATE_FORMAT(next_bill_date, '%d %m %y') = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL ".Config::TIME_BUFFER_DAYS." DAY), '%d %m %y')";
      $this->safeExecute($query, $result);
      if (empty($result)) return false;
      foreach ($result as $row)
      {
        $ret[]=$row["shop_id"];
      }
      return $ret;
    }

  }

?>