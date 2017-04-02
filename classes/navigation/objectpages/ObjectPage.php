<?php
  abstract class ObjectPage
  {
    protected $createQuery;
    protected $readQuery;
    protected $updateQuery;
    protected $deleteQuery;
    protected $listQuery;
    protected $limit1;
    protected $limit2;
    abstract public function createObj();
    abstract public function readObj();
    abstract public function updateObj();
    abstract public function deleteObj();

    public function listObj()
    {      $dbConnection = new DbSafeConnection();
      Display::executeQueryAndDisplayResult($dbConnection, $this->listQuery, true);
    }

    public function display()
    {
      $operation = $_REQUEST["operation"];
      switch ($operation)
      {        case "":        case "list":
          $this->listObj();
          break;
        case "create":
          $this->createObj();
          break;
        case "read":
          $this->readObj();
          break;
        case "update":
          $this->updateObj();
          break;
        case "delete":
          $this->deleteObj();
          break;
      }    }

    protected function sortLink($name, $colNumber)
    {
      $page = $_REQUEST["page"];
      $operation = $_REQUEST["operation"];

      $displayPage = $_REQUEST["display_page"]; //1, 2...
      if (!$displayPage) $displayPage = 1;

      $sortColumn = $_REQUEST["sort_column"];
      if (!$sortColumn) $sortColumn = 1;

      $sortOrder =  $_REQUEST["sort_order"];
      if (!$sortOrder) $sortOrder = "asc";

      $symbol = "";
      if ($colNumber == $sortColumn)
      {
        $symbol = ($sortOrder == "asc") ? "&#9650;" : "&#9660;";
        $sortOrder = ($sortOrder == "asc") ? "desc" : "asc";
      }
      $str = "<a href=\"index.php?page=$page&operation=$operation&display_page=$displayPage&sort_order=$sortOrder&sort_column=$colNumber\">$name ".$symbol."</a>";
      return $str;
    }
  }
?>
