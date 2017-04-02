<?php
  class Database
  {
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $id;

    function __construct($dbName, $dbUser, $dbPass, $id = null)
    {
      $this->dbName = $dbName;
      $this->dbUser = $dbUser;
      $this->dbPass = $dbPass;
      $this->id = $id;
    }

    function getDbName()
    {
      return $this->dbName;
    }

    function getDbUser()
    {
      return $this->dbUser;
    }

    function getDbPass()
    {
      return $this->dbPass;
    }
  }
?>