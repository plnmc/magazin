<?php
  class PleskOperations
  {
    public static function createSubdomain($subDomain)
    {
      if (!Validator::isSubDomain($subDomain, Strings::LENGTH_SUBDOMAIN_NAME,
        Strings::INCORRECT_SUBDOMAIN_NAME)) return;
      $commandToExecute = "sudo /sandbox/subdomain.sh --create \"".$subDomain."\" -domain \""
       .Config::MAIN_DOMAIN."\" -php true -ssl true -same_ssl true 2>&1";
      return SystemOperations::executeCommand($commandToExecute);
    }

    public static function removeSubdomain($subDomain)
    {
      $commandToExecute = "sudo /sandbox/subdomain.sh -r -subdomains \"".$subDomain."\" -domain \""
       .Config::MAIN_DOMAIN."\" 2>&1";
      return SystemOperations::executeCommand($commandToExecute);
    }

    public static function createDatabase($dbName, $dbUser, $dbPass)
    {
      $commandToExecute = "sudo /sandbox/database.sh --create \"".$dbName."\" -domain \""
       .Config::MAIN_DOMAIN."\" -type mysql 2>&1";
      $status = SystemOperations::executeCommand($commandToExecute);
      if ($status !=0) return $status;
      $commandToExecute = "sudo /sandbox/database.sh --update \"".$dbName."\" -add_user \""
       .$dbUser."\" -passwd \"".$dbPass."\" 2>&1";
      return SystemOperations::executeCommand($commandToExecute);
    }

    public static function removeDatabase($dbName)
    {
      $commandToExecute = "sudo /sandbox/database.sh --remove \"".$dbName."\" 2>&1";
      return SystemOperations::executeCommand($commandToExecute);
    }
  }
?>