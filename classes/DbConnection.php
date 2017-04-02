<?php
  // here only one function safeExecute is publicly accessed
  class DbConnection
  {
    protected $dbLink;

    protected function __construct($dbName, $dbUser, $dbPass, $dbHost)
    {
      $dbConnection = @mysql_connect($dbHost, $dbUser, $dbPass);
      if (!$dbConnection)
      {
        Display::append('Could not connect: ' . mysql_error());
      }
      @mysql_query("SET names UTF8");
      $dbSelected = @mysql_select_db($dbName, $dbConnection);
      if (!$dbSelected)
      {
        Display::append('Can\'t use '.$dbName.' : ' . mysql_error());
      }
      $this->dbLink = $dbConnection;
    }

    /*
     * returns true if success, false if error
     * returns associative array in $returnArray:
     * if $returnColumnNames then 0th string contains field names of the result
     * further - result itself
     */
    private function executeQuery($query, &$returnArray = null, $returnColumnNames = false)
    {
      Display::debug("\n<br>[".date("d.m.Y H:i:s")."] Execute query: $query<br>\n");
      $returnArray = null;
      $starttime = microtime(true);
      $result = mysql_query($query, $this->dbLink);
      $endtime = microtime(true);
      Display::debug("\n<br>query took: ".number_format(($endtime-$starttime), 6, ".", "")." seconds<br>\n");
      if (!$result)
      {
        Display::debug("Could not run query ($query) from DB: " . mysql_error());
        return false;
      }
      // Here result can be bool(TRUE) or can be resource (if "select" query was performed)
      //  if result is resource then we need to fill returnArray with returned values
      if (is_resource($result))
      {
        if ($returnColumnNames)
        {
          for ($j=0; $j < mysql_num_fields($result); $j++)
          {
            $row[mysql_field_name($result, $j)] = mysql_field_name($result, $j);
          }
          $returnArray[] = $row;  // row with column names of the result
        }
        while ($row = mysql_fetch_assoc($result))
        {          $returnArray[] = $row;        }
      }
      return true;    }

    /*
     * returns true if success, false if error
     * returns associative array in $returnArray:
     * if $returnColumnNames then 0th string contains field names of the result
     * further - result itself
     */
    public function safeExecute($query, &$returnArray = null, $returnColumnNames = false)
    {
      if (!$this->executeQuery($query, $returnArray, $returnColumnNames))
      {
        return false;
      }
      else
      {        return true;      }
    }

    private function beginTransaction()
    {
      return $this->executeQuery( "START TRANSACTION" );
    }

    private function commitTransaction()
    {
      return $this->executeQuery( "COMMIT" );
    }

    private function rollbackTransaction()
    {
      return $this->executeQuery( "ROLLBACK" );
    }

    public function performTransaction($queriesArray)
    {
      $res = true;
      $this->beginTransaction();
      foreach ($queriesArray as $query)
      {
        $res = $this->executeQuery($query);
        if (!$res)
        {
          break;
        }
      }
      if ($res)
      {
        $this->commitTransaction();
      }
      else
      {
        $this->rollbackTransaction();
        Display::append("Rollback in transaction!");
      }
      return $res;
    }

  }
?>
