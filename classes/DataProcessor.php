<?php
  class DataProcessor
  {
    //if magic quotes turned on, we should strip them
    //also we shall strip all html etc.
    public static function stripAll()
    {
      function stripArray(&$arr)
      {        foreach($arr as &$value)
        {
          if (get_magic_quotes_gpc())
          {
            $value = stripslashes($value);
          }
          $value = htmlspecialchars($value, ENT_QUOTES, "UTF-8");
//          $value = strip_tags($value);
//          $value = stripslashes($value);
//          $value = addslashes($value);
        }
      }
      stripArray($_GET);
      stripArray($_POST);
      stripArray($_REQUEST);
//      stripArray($_SESSION);
    }

    public static function getSessionOrRequestVariable($varName)
    {
      $retValue = null;      if (isset($_REQUEST[$varName])) $retValue = $_REQUEST[$varName];
      if (isset($_SESSION[$varName])) $retValue = $_SESSION[$varName];
      return $retValue;    }

    public static function generateMD5($strArray)
    {
      $divider = '!!!###!!!';
      $str = implode($divider, $strArray);
      $str = md5($str);
      return $str;    }
    public static function generateRandomString($length)
    {      $chars = "abcdefgh123456789";
      $len = strlen($chars);
      $randomString = "";
      for ($i=0; $i<$length; $i++)
      {        $randomString .= substr($chars, rand(0, $len-1), 1);      }
      return $randomString;    }

    public static function generateRandomStringExtended($length = 8)
    {      $str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      for ($i = 0, $passwd = ''; $i < $length; $i++)
        $passwd .= substr($str, mt_rand(0, strlen($str) - 1), 1);
      return $passwd;
    }

    public static function unhtmlspecialchars( $string )
    {
        $string = str_replace ( '&amp;', '&', $string );
        $string = str_replace ( '&#039;', '\'', $string );
        $string = str_replace ( '&quot;', '"', $string );
        $string = str_replace ( '&lt;', '<', $string );
        $string = str_replace ( '&gt;', '>', $string );

        return $string;
    }

  	static function strtolower($str)
  	{
  		if (function_exists('mb_strtolower'))
  			return mb_strtolower($str, 'utf-8');
  		return strtolower($str);
  	}

  	static function strtoupper($str)
  	{
  		if (function_exists('mb_strtoupper'))
  			return mb_strtoupper($str, 'utf-8');
  		return strtoupper($str);
  	}

  	static function ucfirst($str)
  	{
  		return self::strtoupper(self::substr($str, 0, 1)).self::substr($str, 1);
  	}

  	static function substr($str, $start, $length = false, $encoding = 'utf-8')
  	{
  		if (function_exists('mb_substr'))
  			return mb_substr($str, $start, ($length === false ? self::strlen($str) : $length), $encoding);
  		return substr($str, $start, $length);
  	}

  	static function strlen($str)
  	{
  		if (function_exists('mb_strlen'))
  			return mb_strlen($str, 'utf-8');
  		return strlen($str);
  	}

    static function rmdirRecursive($dir)
    {
      $files = scandir($dir);
      array_shift($files);    // remove '.' from array
      array_shift($files);    // remove '..' from array

      foreach ($files as $file)
      {
        $file = $dir . '/' . $file;
        if (is_dir($file))
        {
          self::rmdirRecursive($file);
          rmdir($file);
        }
        else
        {
          unlink($file);
        }
      }
      return rmdir($dir);
    }

    static function sendMail($to, $subject, $body, $from = Config::SUPPORT_EMAIL)
    {      $headers =  'From: ' . $from . "\n" .
                  'Reply-To: ' . $from . "\n" .
                  'Content-Type: text/html; charset=utf-8';
      $body = str_replace("\n.", "\n..", $body);
      $sentSuccess = mail($to, '=?utf-8?b?'.base64_encode($subject).'?=', $body, $headers);
      if (!$sentSuccess)
      {
        Display::append(sprintf(Strings::EMAIL_NOT_SENT, $to));
      }
      return $sentSuccess;    }

    public static function OnOff($value)
    {      $arr = array (0=> "OFF", 1=> "ON");
      return $arr[$value];    }
  }
?>
