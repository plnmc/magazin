<?php
  class Display
  {
    const DEFAULT_HEADER = "<table border=1 cellspacing=0>";
    private static $output;
    private static $log;

    public static function displayDbResultInTable($dbResult, $header = self::DEFAULT_HEADER)
    {
      if ($dbResult == null)
      {        Display::append("Result is empty!");      }
      else
      {        Display::append($header);
        foreach ($dbResult as $row)
        {
          Display::append("<tr>");
          foreach ($row as $key=>$value)
          {
            Display::append("<td>$value");
          }
        }
        Display::append("</table>");
      }    }

    public static function executeQueryAndDisplayResult($dbConnection, $query,
      $returnColumnNames = false, $header = self::DEFAULT_HEADER)
    {
      $result = null;
      $dbConnection->safeExecute($query, $result, $returnColumnNames);
      self::displayDbResultInTable($result, $header);
    }

    public static function append($str)
    {      self::$output .= $str;
      if (Config::LOGGING)
      {
        self::$log .= $str;
      }
    }

    public static function appendError($str)
    {
      $str = "<div class=\"error\">".$str."</div>";
      self::$output .= $str;
      if (Config::LOGGING)
      {
        self::$log .= $str;
      }
    }

    public static function appendJSError($str)
    {
      $str = str_replace('"', '\\"', $str);
      $str = "<script language=javascript>window.alert(\"".$str."\");</script>";
      self::$output .= $str;
      if (Config::LOGGING)
      {
        self::$log .= $str;
      }
    }

    public static function debug($str)
    {
      if (Config::DEBUG)
      {        self::$output .= $str;
      }
      if (Config::LOGGING)
      {
        self::$log .= $str;
      }
    }

    public static function show()
    {
      header('Content-type: text/html; charset=utf-8');
      echo "<html>
      <head>
      	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf8\" />
      </head>
      <body>";
      echo self::$output;
      echo "</body>
       </html>";
      self::writeLog();
    }

    public static function showInPage()
    {
      echo self::$output;
      self::writeLog();
    }

    private static function writeLog()
    {      $logFile = Config::LOGFILE;
      $fh = fopen($logFile, 'a');
      fwrite($fh, "\r\n-------------------\r\n"
        .date("d.m.Y H:i:s")
        ." ".$_SERVER['REQUEST_URI']
        ."\r\n-------------------\r\n\r\n");
      fwrite($fh, self::$log."\r\n\r\n");
      fclose($fh);
    }

  }
?>
