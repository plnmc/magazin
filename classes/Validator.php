<?php
  class Validator
  {
    //each function in this class should return true or false
    //If function returns false, it should print $errorMessage

    public static function lengthIsOk($variableToCheck, $minLen, $maxLen, $errorMessage)
    {
      if ((strlen($variableToCheck) < $minLen) || (strlen($variableToCheck) > $maxLen))
      {
        Display::appendError(sprintf($errorMessage, $minLen, $maxLen));
        return false;
      }
      return true;
    }

    public static function regexpIsOk($variableToCheck, $regexp, $errorMessage)
    {
      if (!preg_match($regexp, $variableToCheck))
      {
        Display::appendError($errorMessage);
        return false;
      }
      return true;
    }

    public static function equals($variable1, $variable2, $errorMessage)
    {
      if ($variable1 != $variable2)
      {
        Display::appendError($errorMessage);
        return false;
      }
      return true;
    }

    //validates that variables are equal, with extended error message
    public static function equalsPrettyError($actual, $expected, $errorMessage, $errorParameter)
    {
      if ($actual != $expected)
      {
        Display::appendError(sprintf($errorMessage, $errorParameter, $expected));
        return false;
      }
      return true;
    }

    public static function isConfirmation($variableToCheck, $errorMessage)
    {
      return self::regexpIsOk($variableToCheck,
        '/^[a-z0-9]{32}$/ui',
        $errorMessage);
    }

    public static function fullConfirmationCheck($md5)
    {      //if confirmation key is not valid at all
      if (!Validator::isConfirmation($md5, Strings::INCORRECT_CONFIRMATION_LINK)) return false;
      //if request with this md5 does not exist
      if (!Validator::isDomainRequestExistsByMD5($md5, Strings::INCORRECT_CONFIRMATION_LINK)) return false;
      //if _fresh_ request with this md5 does not exist
      if (!Validator::isFreshDomainRequestExistsByMD5($md5, Strings::REGISTRATION_EXPIRED)) return false;
      //if domain is not free
      if (!Validator::isDomainFreeByMD5($md5, Strings::EXISTENT_SUBDOMAIN_NAME)) return false;
      return true;    }

    public static function isEmail($variableToCheck, $errorMessage)
    {
      return self::regexpIsOk($variableToCheck,
        '/^[a-z0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z0-9]+[._a-z0-9-]*\.[a-z0-9]+$/ui',
        $errorMessage);
    }

    public static function isName($variableToCheck, $errorMessageLength, $errorMessageRegexp)
    {
      if (self::lengthIsOk($variableToCheck, 1, 256, $errorMessageLength))
      {
        return self::regexpIsOk($variableToCheck, '/^[^0-9!<>,;?=+()@#"°{}_$%:]*$/ui',
          $errorMessageRegexp);
      }
      return false;
    }

    public static function isPhone($variableToCheck, $errorMessage)
    {
      if (self::lengthIsOk($variableToCheck, 7, 100, $errorMessage))
      {
        return true;
      } else
      {
        return false;
      }
    }

    public static function isCity($variableToCheck, $errorMessage)
    {
      if (self::lengthIsOk($variableToCheck, 1, 256, $errorMessage))
      {
        return self::regexpIsOk($variableToCheck, '/^[^0-9!<>,;?=+()@#"°{}_$%:]*$/ui',
          $errorMessage);
      }
      return false;
    }

    public static function isPassword($pass1, $pass2, $errorMessageLength,
      $errorMessageRegexp, $errorMessageMatch)
    {
      $minLen = 8;
      $maxLen = 32;
      if (self::lengthIsOk($pass1, $minLen, $maxLen, $errorMessageLength))
      {
        if (self::regexpIsOk($pass1, '/^[.a-z_0-9-!@#$%^*()]{'.$minLen.','.$maxLen.'}$/ui',
          $errorMessageRegexp))
        {
          return self::equals($pass1, $pass2, $errorMessageMatch);
        }
      }
      return false;
    }

    public static function isSubDomain($variableToCheck, $errorMessageLength, $errorMessageRegexp)
    {
      if (self::lengthIsOk($variableToCheck, 2, 63, $errorMessageLength))
      {
        return self::regexpIsOk($variableToCheck, '/^([a-z]){1}([0-9a-z-]){1,62}$/ui',
          $errorMessageRegexp);
      }
      return false;
    }

    public static function isDomainFree($domain, $errorMessage)
    {
      $dbConnection = new DbSafeConnection();
      if ($dbConnection->isDomainTaken($domain))
      {
        Display::appendError(sprintf($errorMessage, $domain));
        return false;
      }
      return true;
    }

    public static function isSubDomainFree($subDomain, $errorMessage)
    {
      return self::isDomainFree($subDomain . "." . Config::MAIN_DOMAIN, $errorMessage);
    }

    public static function isDomainFreeByMD5($md5, $errorMessage)
    {
      $dbConnection = new DbSafeConnection();
      $domainName = $dbConnection->getDomainRequestNameByMD5($md5);
      return self::isDomainFree($domainName, $errorMessage);
    }

    public static function isFreshSubDomainRequestAbsent($subDomain, $email, $errorMessage)
    {
      $dbConnection = new DbSafeConnection();
      if ($dbConnection->isFreshSubDomainRequestExists($subDomain, $email))
      {
        Display::appendError(sprintf($errorMessage, $subDomain . "." . Config::MAIN_DOMAIN));
        return false;
      }
      return true;
    }

    public static function isDomainRequestExistsByMD5($md5, $errorMessage)
    {
      $dbConnection = new DbSafeConnection();
      if (!$dbConnection->getDomainRequestIdByMD5($md5))
      {
        Display::appendError(sprintf($errorMessage));
        return false;
      }
      return true;
    }

    //if fresh request with this md5 exists
    public static function isFreshDomainRequestExistsByMD5($md5, $errorMessage)
    {
      $dbConnection = new DbSafeConnection();
      //if domain request with specified MD5 is not expired - return request Id, else return empty string
      if (!$dbConnection->getFreshDomainRequestIdByMD5($md5))
      {
        Display::appendError($errorMessage);
        return false;
      }
      return true;
    }

    //validates that string enclosed with ""
    public static function isStringQuoted($str, $errorMessage, $strIndex)
    {
      if ((substr($str, 0, 6) != "&quot;") || (substr($str, -6) != "&quot;"))
      {
        Display::appendError(sprintf($errorMessage, $strIndex));
        return false;
      }
      return true;
    }

    //validates that in import string type is correct
    public static function isImportTypeCorrect($str, $errorMessage, $strIndex)
    {
      switch ($str)
      {
        case ImportOperations::TYPE_GROUP:
        case ImportOperations::TYPE_ELEMENT:
          break;
        default:
          Display::appendError(sprintf($errorMessage, $strIndex));
          return false;
      }
      return true;
    }

    public static function isKeyExistsInArray($key, $array, $errorMessage, $strIndex)
    {      if (!array_key_exists($key, $array))
      {
          Display::appendError(sprintf($errorMessage, $strIndex, $key));
          return false;
      }
      return true;
    }

    public static function isKeyAbsentInArray($key, $array, $errorMessage, $strIndex)
    {
      if (array_key_exists($key, $array))
      {
          Display::appendError(sprintf($errorMessage, $strIndex, $key));
          return false;
      }
      return true;
    }

    public static function isElementAbsentInArray($element, $array, $errorMessage, $strIndex)
    {
      if (in_array($element, $array))
      {
          Display::appendError(sprintf($errorMessage, $strIndex, $element));
          return false;
      }
      return true;
    }

  }


?>
