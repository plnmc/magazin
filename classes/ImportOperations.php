<?php
  class ImportOperations
  {
    const TYPE_GROUP = "GR";
    const TYPE_ELEMENT = "EL";
    const FIELDS_NUMBER_GROUP = 4;
    const FIELDS_NUMBER_ELEMENT = 7;
    const TOP_CATEGORY_KEY = "Null";

    /*
Example
"GR";"00000055";"PHILIPS";"Null"
"GR";"00000035";"Лампы  накаливания";"00000055"
"GR";"00000015";"ГРУШИ";"00000035"
"EL";"000826";"Philips A55 100W E27 CL ГРУШ/1 в упак";"00000015";"13.6";"руб.";"шт"
"EL";"000827";"Philips A55 100W E27 FR ГРУШ/1 в упак";"00000015";"13.6";"руб.";"шт"

for category and product can be the same key
column "depth" will be generated for categories

      1. validate CSV
        1. 0th column (type)
        2. number of columns depending on type)
        3. No category/product with such key before (categories IDs should be unique)
        4. parent category should exist in CSV before
        ////
      2. import  CSV
        1. import all categories
           0. disable all categories
           0.5-   make sure table ps_1c_map_categories exists or create it
           1. find ID of category in ps_1c_map by key
           2. if not found -
              insert into ps_1c_map_categories values (ID (=null, autoincrement), key) and return ID
              insert into category
           3. update category with enabling
           4.  calculate depth
           5.  generate link_rewrite
           6.   regenerate Ntree
        2. import all products
           0. disable all products
           1. find ID of product by key and update product_lang with enabling
           2. if not found -
              make sure table ps_1c_map_products exists or create it
              insert into ps_1c_map_products values (ID (=null, autoincrement), key) and return ID
              insert into product_lang
    */

    public static function parseAndValidateCSV($csv)
    {      $textAsArray = explode("\r\n", $csv);
      $strIndex = 0;
      $categoryKeysAndDepths = array(self::TOP_CATEGORY_KEY => 0);  // key => depth
      $productKeys = array();
      $returnArray = array();
      foreach ($textAsArray as $str)
      {
        if ($str == "")     //empty strings skipped and not counted
        {          continue;        }
        $strIndex++;
        if (!Validator::isStringQuoted($str, Strings::IMPORT_STRING_NOT_QUOTED, $strIndex)) return false;
        $str = substr($str, 6, -6);  // remove first and last &quot;
        $fields = explode("&quot;;&quot;", $str);   // explode by 3 symbols ";"

        //replace doubled quotes with single
        for ($i=0; $i < count($fields); $i++)
        {          $fields[$i] = str_replace("&quot;&quot;", "&quot;", $fields[$i]);        }

       //check first field (type - category or product)
        $type = $fields[0];
        if (!Validator::isImportTypeCorrect($type, Strings::IMPORT_STRING_TYPE_NOT_CORRECT, $strIndex)) return false;

        //check number of fields
        switch ($type)
        {
          case ImportOperations::TYPE_GROUP:
            if (!Validator::equalsPrettyError(count($fields), ImportOperations::FIELDS_NUMBER_GROUP, Strings::IMPORT_STRING_FIELD_NUMBER_NOT_CORRECT, $strIndex)) return false;
            break;
          case ImportOperations::TYPE_ELEMENT:
            if (!Validator::equalsPrettyError(count($fields), ImportOperations::FIELDS_NUMBER_ELEMENT, Strings::IMPORT_STRING_FIELD_NUMBER_NOT_CORRECT, $strIndex)) return false;
        }

        $key = $fields[1];
        $name = $fields[2];
        $parent = $fields[3];

        switch ($type)
        {          case ImportOperations::TYPE_GROUP:
            //parent category should exist
            if (!Validator::isKeyExistsInArray($parent, $categoryKeysAndDepths, Strings::IMPORT_NO_SUCH_PARENT_CATEGORY, $strIndex)) return false;
            //category with current key should be unique
            if (!Validator::isKeyAbsentInArray($key, $categoryKeysAndDepths, Strings::IMPORT_CATEGORY_KEY_ALREADY_EXISTS, $strIndex)) return false;
            $depth = $categoryKeysAndDepths[$parent] + 1;            $categoryKeysAndDepths[$key] = $depth;
            $fields[4] = $depth;
            break;
          case ImportOperations::TYPE_ELEMENT:
            //parent category should exist
            if (!Validator::isKeyExistsInArray($parent, $categoryKeysAndDepths, Strings::IMPORT_NO_SUCH_PARENT_CATEGORY, $strIndex)) return false;
            //product with current key should be unique
            if (!Validator::isElementAbsentInArray($key, $productKeys, Strings::IMPORT_PRODUCT_KEY_ALREADY_EXISTS, $strIndex)) return false;
            $productKeys[] = $key;        }
        $returnArray[] = $fields;
      }
      return $returnArray;
    }

    public static function importCategories($dbConnection, $parsedCSV)
    {
      $dbConnection->createImportTablesIfNotExist();
      $dbConnection->disableMappedCategories();
      $startId = $dbConnection->getMaxCategoryId() + 1;
      Display::debug("Start ID is $startId");
      foreach ($parsedCSV as $fields)
      {
        $type = $fields[0];
        if ($type != ImportOperations::TYPE_GROUP) continue;
        $key = $fields[1];
        $name = DataProcessor::unhtmlspecialchars($fields[2]);
        $parentKey = $fields[3];
        $depth = $fields[4];
        $link = "";

        Display::debug("Find category with key=$key in map table<br>");
        $categoryId = $dbConnection->getCategoryIdByKey($key);
        if ($categoryId == null) //if category not found
        {
          Display::debug("No such category in map table, will insert with ID $startId and key ".$key." ".$name);
          $categoryId = $startId;
          $dbConnection->insertCategoryMapping($key, $categoryId);
          $startId++;        }
        $parentId = self::calculateParentIdByKey($dbConnection, $parentKey);
        $link = self::link_rewrite_ru($name);
        Display::debug("Find category with ID=$categoryId in ps_category to decide, update existing or insert new<br>");
        if ($dbConnection->isCategoryExistsById($categoryId))
        {
          $dbConnection->updateCategory($categoryId, $name, $parentId, $depth, $link);
        }
        else
        {          $dbConnection->insertCategory($categoryId, $name, $parentId, $depth, $link);
        }
      }
      self::regenerateEntireNtree($dbConnection);
    }

    public static function importProducts($dbConnection, $parsedCSV)
    {
      $dbConnection->createImportTablesIfNotExist();
      $dbConnection->disableMappedProducts();
      $startId = $dbConnection->getMaxProductId() + 1;
      Display::debug("Start ID is $startId");
      foreach ($parsedCSV as $fields)
      {
        $type = $fields[0];
        if ($type != ImportOperations::TYPE_ELEMENT) continue;
        $key = $fields[1];
        $name = DataProcessor::unhtmlspecialchars($fields[2]);
        $parentKey = $fields[3];
        $price = $fields[4];
        $unit = DataProcessor::unhtmlspecialchars($fields[6]);
        $link = self::link_rewrite_ru($name);

        Display::debug("Find product with key=$key in map table<br>");
        $productId = $dbConnection->getProductIdByKey($key);
        if ($productId == null) //if product not found
        {
          Display::debug("No such Product in map table, will insert with ID $startId and key ".$key." ".$name);
          $productId = $startId;
          $dbConnection->insertProductMapping($key, $productId);
          $startId++;
        }
        $parentId = self::calculateParentIdByKey($dbConnection, $parentKey);
        Display::debug("Find product with ID=$productId in ps_product to decide, update existing or insert new<br>");
        $price = round ($price / 1.180000 , 6); //NDS
        if ($dbConnection->isProductExistsById($productId))
        {
          $dbConnection->updateProduct($productId, $name, $parentId, $price, $link);
        }
        else
        {
          $dbConnection->insertProduct($productId, $name, $parentId, $price, $link);
        }
      }
    }


    private static function calculateParentIdByKey($dbConnection, $parentKey)
    {
      if ($parentKey == self::TOP_CATEGORY_KEY)
      {
        return 1;
      }
      return $dbConnection->getCategoryIdByKey($parentKey);
    }
///// from prestashop

  	/**
  	  * Re-calculate the values of all branches of the nested tree  - code taken from Category.php
  	  */
  	private static function regenerateEntireNtree($dbConnection)
    {
      $categories = null;
      $dbConnection->safeExecute('SELECT id_category, id_parent FROM '.Config::DB_TABLE_PREFIX.'category ORDER BY id_category ASC', $categories);
      $categoriesArray = array();
      foreach ($categories AS $category)
        $categoriesArray[(int)$category['id_parent']]['subcategories'][(int)$category['id_category']] = 1;
      $n = 1;
      self::subTree($dbConnection, $categoriesArray, 1, $n);
    }

    private static function subTree($dbConnection, &$categories, $id_category, &$n)
    {
      $left = (int)$n++;
      if (isset($categories[(int)$id_category]['subcategories']))
        foreach ($categories[(int)$id_category]['subcategories'] AS $id_subcategory => $value)
          self::subTree($dbConnection, $categories, (int)$id_subcategory, $n);
      $right = (int)$n++;

      $dbConnection->safeExecute('UPDATE '.Config::DB_TABLE_PREFIX.'category SET nleft = '.(int)$left.', nright = '.(int)$right.' WHERE id_category = '.(int)$id_category.' LIMIT 1');
  	}

  	private static function strlen($str)
  	{
  		if (is_array($str))
  			return false;
  		$str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
  		if (function_exists('mb_strlen'))
  			return mb_strlen($str, 'utf-8');
  		return strlen($str);
  	}

  	private static function substr($str, $start, $length = false, $encoding = 'utf-8')
  	{
  		if (is_array($str))
  			return false;
  		if (function_exists('mb_substr'))
  			return mb_substr($str, (int)($start), ($length === false ? self::strlen($str) : (int)($length)), $encoding);
  		return substr($str, $start, ($length === false ? self::strlen($str) : (int)($length)));
  	}

  	private static function strtolower($str)
  	{
  		if (is_array($str))
  			return false;
  		if (function_exists('mb_strtolower'))
  			return mb_strtolower($str, 'utf-8');
  		return strtolower($str);
  	}

  	private static function link_rewrite($str, $utf8_decode = false)
  	{
  		$purified = '';
  		$length = self::strlen($str);
  		if ($utf8_decode)
  			$str = utf8_decode($str);
  		for ($i = 0; $i < $length; $i++)
  		{
  			$char = self::substr($str, $i, 1);
  			if (self::strlen(htmlentities($char)) > 1)
  			{
  				$entity = htmlentities($char, ENT_COMPAT, 'UTF-8');
  				$purified .= $entity{1};
  			}
  			elseif (preg_match('|[[:alpha:]]{1}|u', $char))
  				$purified .= $char;
  			elseif (preg_match('<[[:digit:]]|-{1}>', $char))
  				$purified .= $char;
  			elseif ($char == ' ')
  				$purified .= '-';
  			elseif ($char == '\'')
  				$purified .= '-';
  		}
  		return trim(self::strtolower($purified));
  	}


  	private static function link_rewrite_ru($str, $utf8_decode = false)
  	{
      if (preg_match('/[А-Яа-я]+/', $str)){
  			$cyr = array('а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
  			$lat = array('a', 'b', 'v', 'g', 'd', 'e', 'e', 'j', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'shh', '', 'y', '', 'e', 'u', 'ja');
  			$str = mb_strtolower($str, 'utf-8');
  			$str = str_replace($cyr, $lat, $str);
  		}
  		return self::link_rewrite($str, $utf8_decode);
  	}


  }


?>
